<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Patient\StoreMedicationWalletRequest;
use App\Http\Requests\API\V1\Patient\ToggleWalletRequest;
use App\Http\Requests\API\V1\Patient\UpdateMedicationWalletRequest;
use App\Http\Requests\API\V1\Patient\UpdatePillsRequest;
use App\Http\Resources\API\V1\Patient\MedicationWalletResource;
use App\Models\MedicationPatient;
use App\Models\MedicationSchedule;
use App\Services\MedicalSafetyEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Patient Medication Wallet & Scheduling Engine (FR-P-2.3, FR-P-2.4, FR-P-8.1).
 *
 * Full CRUD for medication_patients with dynamic schedule decomposition
 * into medication_schedules based on frequency protocol:
 *
 * - daily:          Creates schedules with dose_time and day_of_week = null
 * - specific_days:  Creates schedules with dose_time + specific day_of_week ints
 * - as_needed:      No schedules created (PRN protocol)
 *
 * Temporary state (state = 'temporary') tracks start_date/end_date on the
 * medication_patient record without affecting schedule generation logic.
 *
 * Flow (store):
 * 1. Check for duplicate medication_id in wallet. Block with 409 if exists.
 * 2. If is_active = true, run MedicalSafetyEngine::evaluate().
 *    Block with 422 if contraindicated.
 * 3. Create medication_patient record.
 * 4. Generate medication_schedules based on frequency protocol.
 * 5. Return created resource with schedules loaded.
 *
 * Flow (update):
 * 1. Validate input — only provided fields are updated.
 * 2. If schedules are provided, delete old schedules and re-generate.
 * 3. Return updated resource.
 *
 * Flow (toggle):
 * 1. Flip is_active to the requested value.
 * 2. When activating (false → true), re-evaluate safety.
 * 3. Block with 422 if contraindicated.
 */
class MedicationWalletController extends Controller
{
    public function __construct(
        private readonly MedicalSafetyEngine $safetyEngine,
    ) {}

    /**
     * List medication wallet entries (FR-P-2.3).
     *
     * Supports ?filter=active (is_active = true) or ?filter=history (is_active = false).
     * Defaults to all entries when no filter is specified.
     *
     * @queryParam filter string Optional. "active" or "history".
     */
    public function index(Request $request): JsonResponse
    {
        $patient = $request->user()->patient;

        $query = $patient->medicationPatients()
            ->with(['medication.usage.title.category', 'medicationSchedules']);

        if ($request->filled('filter')) {
            if ($request->input('filter') === 'active') {
                $query->where('is_active', true);
            } elseif ($request->input('filter') === 'history') {
                $query->where('is_active', false);
            }
        }

        $items = $query->latest()->get();

        return response()->json([
            'data' => MedicationWalletResource::collection($items),
        ]);
    }

    /**
     * Add a medication to the patient wallet (FR-P-2.3, FR-P-8.1, FR-P-4).
     *
     * Before saving with is_active = true, evaluates safety via
     * MedicalSafetyEngine. If a severe drug-drug interaction or disease
     * contraindication is detected, returns 422 with details.
     *
     * Schedule generation logic:
     * - daily:         Creates records with dose_time only (day_of_week = null).
     * - specific_days: Creates records with dose_time and day_of_week per entry.
     * - as_needed:     No schedules generated.
     */
    public function store(StoreMedicationWalletRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $patient = $request->user()->patient;

        $alreadyTaking = $patient->medicationPatients()
            ->where('medication_id', $validated['medication_id'])
            ->exists();

        if ($alreadyTaking) {
            return response()->json([
                'message' => 'You are already taking this medication.',
            ], 409);
        }

        if ($validated['is_active']) {
            $safetyResult = $this->safetyEngine->evaluate($validated['medication_id'], $patient->id);
            if (! $safetyResult['is_safe']) {
                return response()->json([
                    'message' => 'Safety check failed. This medication is contraindicated.',
                    'conflicts' => $safetyResult['conflicts'],
                ], 422);
            }
        }

        $medicationPatient = MedicationPatient::create([
            'medication_id' => $validated['medication_id'],
            'patient_id' => $patient->id,
            'state' => $validated['state'],
            'chronic_id' => $validated['chronic_id'] ?? null,
            'dosage' => $validated['dosage'],
            'available_pills' => $validated['available_pills'] ?? null,
            'frequency' => $validated['frequency'],
            'refill_risk' => false,
            'instructions_before' => $validated['instructions_before'] ?? null,
            'instructions_after' => $validated['instructions_after'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'is_active' => $validated['is_active'],
        ]);

        if ($validated['frequency'] !== 'as_needed' && ! empty($validated['schedules'])) {
            $this->generateSchedules($medicationPatient, $validated['schedules']);
        }

        $medicationPatient->load(['medication.usage.title.category', 'medicationSchedules']);

        return response()->json([
            'message' => 'Medication added to wallet.',
            'data' => new MedicationWalletResource($medicationPatient),
        ], 201);
    }

    /**
     * Update a medication wallet entry (FR-P-2.4, FR-P-8.1).
     *
     * Updates only the provided fields. If schedules are included, old
     * schedules are deleted and replaced with the new ones (always replace).
     * For pill-only changes, use the dedicated PATCH /pills endpoint.
     */
    public function update(UpdateMedicationWalletRequest $request, MedicationPatient $medicationPatient): JsonResponse
    {
        $patient = $request->user()->patient;

        if ($medicationPatient->patient_id !== $patient->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validated();

        $fieldsToUpdate = [];
        foreach (['state', 'dosage', 'available_pills', 'frequency', 'instructions_before', 'instructions_after', 'start_date', 'end_date', 'is_active'] as $field) {
            if (array_key_exists($field, $validated)) {
                $fieldsToUpdate[$field] = $validated[$field];
            }
        }

        if ($fieldsToUpdate !== []) {
            $medicationPatient->update($fieldsToUpdate);
        }

        if (! empty($validated['schedules'])) {
            $medicationPatient->medicationSchedules()->delete();

            if ($medicationPatient->frequency !== 'as_needed') {
                $this->generateSchedules($medicationPatient, $validated['schedules']);
            }
        }

        $medicationPatient->load(['medication.usage.title.category', 'medicationSchedules']);

        return response()->json([
            'message' => 'Medication wallet entry updated.',
            'data' => new MedicationWalletResource($medicationPatient),
        ]);
    }

    /**
     * Delete a medication wallet entry (FR-P-2.4).
     *
     * Permanently removes the record. Cascade delete removes
     * linked medication_schedules.
     */
    public function destroy(Request $request, MedicationPatient $medicationPatient): JsonResponse
    {
        $patient = $request->user()->patient;

        if ($medicationPatient->patient_id !== $patient->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $medicationPatient->delete();

        return response()->json([
            'message' => 'Medication wallet entry deleted.',
        ]);
    }

    /**
     * Update only the available_pills count (FR-P-8.4).
     *
     * Dedicated endpoint for adjusting the household supply count
     * without affecting any other wallet or schedule fields.
     */
    public function patchPills(UpdatePillsRequest $request, MedicationPatient $medicationPatient): JsonResponse
    {
        $patient = $request->user()->patient;

        if ($medicationPatient->patient_id !== $patient->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $medicationPatient->update([
            'available_pills' => $request->validated()['available_pills'],
        ]);

        $medicationPatient->load(['medication.usage.title.category', 'medicationSchedules']);

        return response()->json([
            'message' => 'Pill count updated.',
            'data' => new MedicationWalletResource($medicationPatient),
        ]);
    }

    /**
     * Toggle the is_active state of a medication wallet entry (FR-P-2.4).
     *
     * When toggling from inactive to active (is_active = true),
     * runs a full safety re-evaluation. If contraindicated, returns 422.
     */
    public function toggle(ToggleWalletRequest $request, MedicationPatient $medicationPatient): JsonResponse
    {
        $patient = $request->user()->patient;

        if ($medicationPatient->patient_id !== $patient->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validated();

        if ($validated['is_active'] && ! $medicationPatient->is_active) {
            $safetyResult = $this->safetyEngine->evaluate($medicationPatient->medication_id, $patient->id);
            if (! $safetyResult['is_safe']) {
                return response()->json([
                    'message' => 'Safety re-evaluation failed. Cannot activate — this medication is contraindicated.',
                    'conflicts' => $safetyResult['conflicts'],
                ], 422);
            }
        }

        $medicationPatient->update([
            'is_active' => $validated['is_active'],
        ]);

        $medicationPatient->load(['medication.usage.title.category', 'medicationSchedules']);

        return response()->json([
            'message' => $validated['is_active'] ? 'Medication activated.' : 'Medication deactivated.',
            'data' => new MedicationWalletResource($medicationPatient),
        ]);
    }

    /**
     * Generate medication_schedule rows from the provided schedule data.
     *
     * @param  array  $schedules  Array of ['dose_time' => 'HH:MM', 'day_of_week' => int|null]
     */
    private function generateSchedules(MedicationPatient $medicationPatient, array $schedules): void
    {
        $rows = [];

        foreach ($schedules as $entry) {
            if ($medicationPatient->frequency === 'specific_days') {
                if (isset($entry['day_of_week'])) {
                    $rows[] = [
                        'id' => (string) Str::uuid(),
                        'medication_patient_id' => $medicationPatient->id,
                        'dose_time' => $entry['dose_time'],
                        'day_of_week' => $entry['day_of_week'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            } else {
                $rows[] = [
                    'id' => (string) Str::uuid(),
                    'medication_patient_id' => $medicationPatient->id,
                    'dose_time' => $entry['dose_time'],
                    'day_of_week' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if ($rows !== []) {
            MedicationSchedule::insert($rows);
        }
    }
}
