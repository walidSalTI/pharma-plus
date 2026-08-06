<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Patient\StoreChronicDiseaseRequest;
use App\Http\Requests\API\V1\Patient\UpdateChronicDiseaseRequest;
use App\Http\Resources\API\V1\Patient\ChronicDiseaseResource;
use App\Models\ChronicRecord;
use App\Services\MedicalSafetyEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Patient Chronic Disease Management (FR-P-2.1, FR-P-2.4).
 *
 * Full CRUD for the patient's chronic conditions tracking.
 * Patients have absolute authority to manage their chronic_records.
 *
 * Flow (store):
 * 1. Validate input (chronic_disease_id, diagnosis_year, severity).
 * 2. Check for duplicates — same chronic_disease_id per patient.
 * 3. Save the chronic record.
 * 4. Run MedicalSafetyEngine against all active medications in wallet.
 * 5. If high-risk conflict found, attach warning payload to response.
 *
 * Flow (update):
 * 1. Validate input (diagnosis_year, severity).
 * 2. Update the record — only metadata fields (no disease swap).
 *
 * Flow (destroy):
 * 1. Delete the chronic record (cascading handled by DB).
 */
class ChronicDiseaseController extends Controller
{
    public function __construct(
        private readonly MedicalSafetyEngine $safetyEngine,
    ) {}

    /**
     * List all chronic disease records for the authenticated patient (FR-P-2.1).
     *
     * Returns all diagnosed conditions from chronic_records joined with
     * the global chronic_diseases catalog.
     */
    public function index(Request $request): JsonResponse
    {
        $patient = $request->user()->patient;

        $records = $patient->chronicRecords()
            ->with('chronicDisease')
            ->latest()
            ->get();

        return response()->json([
            'data' => ChronicDiseaseResource::collection($records),
        ]);
    }

    /**
     * Attach a new chronic disease record (FR-P-2.1).
     *
     * Prevents duplicate entries for the same chronic_disease_id.
     * After saving, evaluates all active medications in the patient's
     * wallet against the newly added condition. If any active medication
     * contains an ingredient flagged as high-risk for this disease,
     * a safety warning is appended to the response.
     */
    public function store(StoreChronicDiseaseRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $patient = $request->user()->patient;

        $exists = $patient->chronicRecords()
            ->where('chronic_disease_id', $validated['chronic_disease_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'This chronic disease is already recorded.',
            ], 409);
        }

        $record = ChronicRecord::create([
            'chronic_disease_id' => $validated['chronic_disease_id'],
            'patient_id' => $patient->id,
            'diagnosis_year' => $validated['diagnosis_year'],
            'severity' => $validated['severity'] ?? null,
        ]);

        $record->load('chronicDisease');

        $warnings = [];
        $activeMedications = $patient->medicationPatients()
            ->where('is_active', true)
            ->with('medication.activeIngredients', 'medication.product')
            ->get();

        foreach ($activeMedications as $mp) {
            $safetyResult = $this->safetyEngine->evaluate($mp->medication_id, $patient->id);
            if (! $safetyResult['is_safe']) {
                $warnings[] = [
                    'medication_patient_id' => $mp->id,
                    'trade_name' => $mp->medication?->product?->name,
                    'message' => 'This medication may be contraindicated with the newly added condition.',
                ];
            }
        }

        $response = [
            'message' => 'Chronic disease recorded successfully.',
            'data' => new ChronicDiseaseResource($record),
        ];

        if ($warnings !== []) {
            $response['warnings'] = [
                'safety_alert' => true,
                'contraindications_detected' => count($warnings),
                'medications_affected' => $warnings,
            ];
        }

        return response()->json($response, 201);
    }

    /**
     * Update a chronic record's metadata (FR-P-2.4).
     *
     * Only diagnosis_year and severity can be modified.
     * The disease reference (chronic_disease_id) is immutable.
     */
    public function update(UpdateChronicDiseaseRequest $request, ChronicRecord $chronicRecord): JsonResponse
    {
        $patient = $request->user()->patient;

        if ($chronicRecord->patient_id !== $patient->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validated();

        $chronicRecord->update([
            'diagnosis_year' => $validated['diagnosis_year'] ?? $chronicRecord->diagnosis_year,
            'severity' => array_key_exists('severity', $validated) ? $validated['severity'] : $chronicRecord->severity,
        ]);

        $chronicRecord->load('chronicDisease');

        return response()->json([
            'message' => 'Chronic disease record updated.',
            'data' => new ChronicDiseaseResource($chronicRecord),
        ]);
    }

    /**
     * Detach a chronic disease record (FR-P-2.4).
     *
     * Permanently deletes the chronic record link.
     * Foreign key cascade will handle related medication_patients
     * references (chronic_id set to null via nullOnDelete).
     */
    public function destroy(Request $request, ChronicRecord $chronicRecord): JsonResponse
    {
        $patient = $request->user()->patient;

        if ($chronicRecord->patient_id !== $patient->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $chronicRecord->delete();

        return response()->json([
            'message' => 'Chronic disease record deleted.',
        ]);
    }
}
