<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Company\BatchScheduleRequest;
use App\Http\Requests\API\V1\Company\StoreScheduleRequest;
use App\Http\Resources\API\V1\Company\ScheduleResource;
use App\Models\Doctor;
use App\Models\WeeklySchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    private function activeCompany(Request $request): mixed
    {
        $company = $request->user()->pharmaceuticalCompany;

        if (! $company) {
            abort(response()->json(['message' => 'Company profile not found.'], 404));
        }

        if ($company->status !== 'active') {
            abort(response()->json(['message' => 'Company must be approved before this action.'], 403));
        }

        return $company;
    }

    /**
     * List schedules.
     *
     * Return schedules for reps owned by the active company, optionally
     * filtered by rep, doctor, status, or date range.
     */
    public function index(Request $request): JsonResponse
    {
        $company = $this->activeCompany($request);
        $repIds = $company->scientificReps()->pluck('id');

        $schedules = WeeklySchedule::with(['scientificRep.user', 'doctor.user'])
            ->whereIn('rep_id', $repIds)
            ->when($request->filled('rep_id'), fn ($query) => $query->where('rep_id', $request->input('rep_id')))
            ->when($request->filled('doctor_id'), fn ($query) => $query->where('doctor_id', $request->input('doctor_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('scheduled_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('scheduled_at', '<=', $request->input('to')))
            ->latest('scheduled_at')
            ->paginate(25);

        return response()->json([
            'data' => ScheduleResource::collection($schedules),
            'meta' => [
                'current_page' => $schedules->currentPage(),
                'last_page' => $schedules->lastPage(),
                'per_page' => $schedules->perPage(),
                'total' => $schedules->total(),
            ],
        ]);
    }

    /**
     * Store schedule.
     *
     * Validate company ownership of the rep and create an upcoming schedule.
     */
    public function store(StoreScheduleRequest $request): JsonResponse
    {
        $company = $this->activeCompany($request);
        $validated = $request->validated();

        if (! $company->scientificReps()->whereKey($validated['rep_id'])->exists()) {
            return response()->json(['message' => 'Representative not found for this company.'], 422);
        }

        if (! $company->doctorAssignments()->where('rep_id', $validated['rep_id'])->where('doctor_id', $validated['doctor_id'])->exists()) {
            return response()->json(['message' => 'Representative is not assigned to this doctor.'], 422);
        }

        $doctor = Doctor::find($validated['doctor_id']);
        if (! $doctor || ! $doctor->isVerified()) {
            return response()->json(['message' => 'Doctor must be verified before scheduling.'], 422);
        }

        $schedule = WeeklySchedule::create([
            'rep_id' => $validated['rep_id'],
            'doctor_id' => $validated['doctor_id'],
            'scheduled_at' => $validated['scheduled_at'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'upcoming',
            'is_reminded' => false,
        ])->load(['scientificRep.user', 'doctor.user']);

        return response()->json([
            'message' => 'Schedule created successfully.',
            'data' => new ScheduleResource($schedule),
        ], 201);
    }

    /**
     * Batch store schedules.
     *
     * Validate all reps belong to the company, then bulk insert upcoming
     * schedule rows in one transaction.
     */
    public function batch(BatchScheduleRequest $request): JsonResponse
    {
        $company = $this->activeCompany($request);
        $validated = $request->validated();
        $companyRepIds = $company->scientificReps()->pluck('id')->all();

        foreach ($validated['schedules'] as $schedule) {
            if (! in_array($schedule['rep_id'], $companyRepIds, true)) {
                return response()->json(['message' => 'One or more representatives do not belong to this company.'], 422);
            }

            if (! $company->doctorAssignments()->where('rep_id', $schedule['rep_id'])->where('doctor_id', $schedule['doctor_id'])->exists()) {
                return response()->json(['message' => 'One or more representatives are not assigned to the specified doctor.'], 422);
            }

            $doctor = Doctor::find($schedule['doctor_id']);
            if (! $doctor || ! $doctor->isVerified()) {
                return response()->json(['message' => 'One or more doctors are not verified.'], 422);
            }
        }

        $now = now();
        $rows = array_map(fn (array $schedule) => [
            'id' => (string) str()->uuid(),
            'rep_id' => $schedule['rep_id'],
            'doctor_id' => $schedule['doctor_id'],
            'scheduled_at' => $schedule['scheduled_at'],
            'notes' => $schedule['notes'] ?? null,
            'status' => 'upcoming',
            'is_reminded' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ], $validated['schedules']);

        DB::table('weekly_schedules')->insert($rows);

        return response()->json([
            'message' => count($rows).' schedules created successfully.',
            'data' => ScheduleResource::collection(
                WeeklySchedule::with(['scientificRep.user', 'doctor.user'])->whereIn('id', array_column($rows, 'id'))->get()
            ),
        ], 201);
    }

    /**
     * Publish schedule.
     *
     * Confirm company ownership and lock the schedule into upcoming status.
     * Push notification delivery is intentionally left for a future channel.
     */
    public function publish(Request $request, WeeklySchedule $schedule): JsonResponse
    {
        $company = $this->activeCompany($request);
        if (! $company->scientificReps()->whereKey($schedule->rep_id)->exists()) {
            return response()->json(['message' => 'Schedule not found.'], 404);
        }

        $schedule->update(['status' => 'upcoming']);

        return response()->json([
            'message' => 'Schedule published successfully.',
            'data' => new ScheduleResource($schedule->fresh()->load(['scientificRep.user', 'doctor.user'])),
        ]);
    }

    /**
     * Cancel schedule.
     *
     * Confirm company ownership and mark the schedule as cancelled.
     */
    public function cancel(Request $request, WeeklySchedule $schedule): JsonResponse
    {
        $company = $this->activeCompany($request);
        if (! $company->scientificReps()->whereKey($schedule->rep_id)->exists()) {
            return response()->json(['message' => 'Schedule not found.'], 404);
        }

        $schedule->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Schedule cancelled successfully.',
            'data' => new ScheduleResource($schedule->fresh()->load(['scientificRep.user', 'doctor.user'])),
        ]);
    }
}
