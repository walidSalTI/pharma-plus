<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Patient\StoreMedicationLogRequest;
use App\Http\Resources\API\V1\Patient\MedicationLogResource;
use App\Models\MedicationLog;
use App\Models\MedicationPatient;
use App\Models\MedicationSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Intake Logging & Smart Cabinet Tracker (FR-P-8.3, FR-P-8.4, FR-P-8.5).
 *
 * Handles patient medication intake logging with automatic cabinet
 * inventory tracking and depletion alerts.
 *
 * Flow (store):
 * 1. Validate input (schedule_id, status, taken_at).
 * 2. Verify the schedule belongs to the authenticated patient.
 * 3. Create medication_logs record.
 * 4. Atomically decrement available_pills on medication_patient.
 * 5. Evaluate depletion risk — if remaining pills < 3-day threshold,
 *    append depletion_alert_triggered warning.
 */
class MedicationLogController extends Controller
{
    /**
     * Log a medication intake event (FR-P-8.3, FR-P-8.4, FR-P-8.5).
     *
     * Records the intake (taken/delayed/skipped), decrements the
     * available_pills counter, and checks for depletion warnings.
     *
     * @bodyParam schedule_id string required The medication schedule UUID.
     * @bodyParam status string required 'taken', 'delayed', or 'skipped'.
     * @bodyParam taken_at string required ISO datetime of the intake.
     * @bodyParam reason string optional Reason for delayed/skipped status.
     */
    public function store(StoreMedicationLogRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $patient = $request->user()->patient;

        $schedule = MedicationSchedule::with('medicationPatient.medication')->findOrFail($validated['schedule_id']);

        if ($schedule->medicationPatient->patient_id !== $patient->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $medicationPatient = $schedule->medicationPatient;

        $log = MedicationLog::create([
            'schedule_id' => $schedule->id,
            'status' => $validated['status'],
            'reason' => $validated['reason'] ?? null,
            'taken_at' => $validated['taken_at'],
        ]);

        $response = [
            'message' => 'Intake logged successfully.',
            'data' => new MedicationLogResource($log),
        ];

        if ($validated['status'] === 'taken' && $medicationPatient->available_pills !== null) {
            $newPillCount = max(0, $medicationPatient->available_pills - 1);
            $medicationPatient->decrement('available_pills');

            $dailyDoseCount = $this->calculateDailyDoseCount($medicationPatient);

            if ($dailyDoseCount > 0 && $newPillCount < ($dailyDoseCount * 3)) {
                $medicationPatient->update(['refill_risk' => true]);

                $response['warning'] = 'depletion_alert_triggered';
                $response['depletion'] = [
                    'remaining_pills' => $newPillCount,
                    'estimated_days_left' => $dailyDoseCount > 0 ? round($newPillCount / $dailyDoseCount, 1) : 0,
                    'daily_dose_count' => $dailyDoseCount,
                ];
            } elseif ($medicationPatient->refill_risk) {
                $medicationPatient->update(['refill_risk' => false]);
            }
        }

        return response()->json($response, 201);
    }

    /**
     * List intake logs for the authenticated patient (FR-P-8.3).
     *
     * Optional ?schedule_id filter to scope logs to a specific schedule.
     * Ordered by most recent first.
     *
     * @queryParam schedule_id string Optional. Filter by schedule UUID.
     */
    public function index(Request $request): JsonResponse
    {
        $patient = $request->user()->patient;

        $query = MedicationLog::query()
            ->whereHas('medicationSchedule.medicationPatient', fn ($q) => $q->where('patient_id', $patient->id))
            ->with(['medicationSchedule.medicationPatient.medication'])
            ->latest('taken_at');

        if ($request->filled('schedule_id')) {
            $query->where('schedule_id', $request->input('schedule_id'));
        }

        $logs = $query->get();

        return response()->json([
            'data' => MedicationLogResource::collection($logs),
        ]);
    }

    /**
     * Calculate the total daily dose count based on frequency and schedules.
     */
    private function calculateDailyDoseCount(MedicationPatient $medicationPatient): int
    {
        if ($medicationPatient->frequency === 'as_needed') {
            return 0;
        }

        $scheduleCount = $medicationPatient->medicationSchedules()->count();

        if ($medicationPatient->frequency === 'specific_days') {
            return $medicationPatient->medicationSchedules()
                ->select('dose_time')
                ->distinct()
                ->count();
        }

        return $scheduleCount;
    }
}
