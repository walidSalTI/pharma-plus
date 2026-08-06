<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Rep;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Rep\RepScheduleResource;
use App\Models\WeeklySchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * List representative schedules.
     *
     * Return the authenticated representative's schedules with doctor
     * and workplace data, optionally filtered by status or date range.
     */
    public function index(Request $request): JsonResponse
    {
        $rep = $request->user()->scientificRep;

        if (! $rep) {
            return response()->json(['message' => 'Representative profile not found.'], 404);
        }

        $schedules = WeeklySchedule::with(['doctor.user'])
            ->where('rep_id', $rep->id)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('scheduled_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('scheduled_at', '<=', $request->input('to')))
            ->orderBy('scheduled_at')
            ->paginate(25);

        return response()->json([
            'data' => RepScheduleResource::collection($schedules),
            'meta' => [
                'current_page' => $schedules->currentPage(),
                'last_page' => $schedules->lastPage(),
                'per_page' => $schedules->perPage(),
                'total' => $schedules->total(),
            ],
        ]);
    }

    /**
     * Show representative schedule.
     *
     * Confirm the schedule belongs to the authenticated representative
     * and return full doctor/workplace context.
     */
    public function show(Request $request, WeeklySchedule $schedule): JsonResponse
    {
        $rep = $request->user()->scientificRep;

        if (! $rep || $schedule->rep_id !== $rep->id) {
            return response()->json(['message' => 'Schedule not found.'], 404);
        }

        return response()->json([
            'data' => new RepScheduleResource($schedule->load(['doctor.user', 'doctor.doctorWorkplaces'])),
        ]);
    }
}
