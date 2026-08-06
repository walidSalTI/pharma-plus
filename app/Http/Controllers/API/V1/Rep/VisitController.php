<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Rep;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Rep\CheckInVisitRequest;
use App\Http\Requests\API\V1\Rep\UpdateVisitNotesRequest;
use App\Http\Resources\API\V1\Rep\RepStatsResource;
use App\Http\Resources\API\V1\Rep\VisitDetailResource;
use App\Http\Resources\API\V1\Rep\VisitResource;
use App\Models\Doctor;
use App\Models\RepresentativeVisit;
use App\Models\WeeklySchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PragmaRX\Google2FA\Google2FA;

class VisitController extends Controller
{
    /**
     * Check in to a visit.
     *
     * Validate TOTP against the doctor's secret key, confirm GPS is inside
     * at least one doctor workplace geofence, ensure the schedule exists
     * for this rep and doctor in upcoming status, block duplicates, create
     * the verified visit, and mark the schedule completed atomically.
     */
    public function checkIn(CheckInVisitRequest $request): JsonResponse
    {
        $rep = $request->user()->scientificRep;

        if (! $rep) {
            return response()->json(['message' => 'Representative profile not found.'], 404);
        }

        $validated = $request->validated();
        $doctor = Doctor::with('doctorWorkplaces')->findOrFail($validated['doctor_id']);

        if (! $doctor->doctor_secret_key || ! (new Google2FA)->verifyKey($doctor->doctor_secret_key, $validated['code'])) {
            return response()->json(['message' => 'QR code expired or invalid.'], 422);
        }

        $insideGeofence = $doctor->doctorWorkplaces->contains(fn ($workplace): bool => $this->distanceInMeters(
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            (float) $workplace->latitude,
            (float) $workplace->longitude,
        ) <= (int) $workplace->radius_meters);

        if (! $insideGeofence) {
            return response()->json(['message' => 'Outside geofence.'], 422);
        }

        $schedule = WeeklySchedule::where('id', $validated['schedule_id'])
            ->where('rep_id', $rep->id)
            ->where('doctor_id', $doctor->id)
            ->where('status', 'upcoming')
            ->first();

        if (! $schedule) {
            return response()->json(['message' => 'No scheduled visit.'], 422);
        }

        if (RepresentativeVisit::where('schedule_id', $schedule->id)->exists()) {
            return response()->json(['message' => 'Visit already checked in.'], 422);
        }

        $visit = DB::transaction(function () use ($rep, $doctor, $schedule, $validated): RepresentativeVisit {
            $visit = RepresentativeVisit::create([
                'doctor_id' => $doctor->id,
                'rep_id' => $rep->id,
                'schedule_id' => $schedule->id,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'scanned_at' => now(),
                'verification_status' => true,
                'notes' => $validated['notes'] ?? null,
            ]);

            $schedule->update(['status' => 'completed']);

            return $visit;
        });

        return response()->json([
            'message' => 'Visit checked in successfully.',
            'data' => new VisitDetailResource($visit->load(['doctor.user', 'weeklySchedule'])),
        ], 201);
    }

    /**
     * Update visit notes.
     *
     * Confirm the visit belongs to the authenticated representative and
     * save post-visit notes using the validated payload.
     */
    public function updateNotes(UpdateVisitNotesRequest $request, RepresentativeVisit $visit): JsonResponse
    {
        $rep = $request->user()->scientificRep;

        if (! $rep || $visit->rep_id !== $rep->id) {
            return response()->json(['message' => 'Visit not found.'], 404);
        }

        $visit->update($request->validated());

        return response()->json([
            'message' => 'Visit notes updated successfully.',
            'data' => new VisitDetailResource($visit->fresh()->load(['doctor.user', 'weeklySchedule'])),
        ]);
    }

    /**
     * List visit history.
     *
     * Return the authenticated representative's visits with doctor names
     * and optional date filters.
     */
    public function index(Request $request): JsonResponse
    {
        $rep = $request->user()->scientificRep;

        if (! $rep) {
            return response()->json(['message' => 'Representative profile not found.'], 404);
        }

        $visits = RepresentativeVisit::with('doctor.user')
            ->where('rep_id', $rep->id)
            ->when($request->filled('from'), fn ($query) => $query->whereDate('scanned_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('scanned_at', '<=', $request->input('to')))
            ->latest('scanned_at')
            ->paginate(25);

        return response()->json([
            'data' => VisitResource::collection($visits),
            'meta' => [
                'current_page' => $visits->currentPage(),
                'last_page' => $visits->lastPage(),
                'per_page' => $visits->perPage(),
                'total' => $visits->total(),
            ],
        ]);
    }

    /**
     * Show visit detail.
     *
     * Confirm ownership and return visit, doctor, and schedule context.
     */
    public function show(Request $request, RepresentativeVisit $visit): JsonResponse
    {
        $rep = $request->user()->scientificRep;

        if (! $rep || $visit->rep_id !== $rep->id) {
            return response()->json(['message' => 'Visit not found.'], 404);
        }

        return response()->json([
            'data' => new VisitDetailResource($visit->load(['doctor.user', 'weeklySchedule'])),
        ]);
    }

    /**
     * Show representative visit stats.
     *
     * Aggregate total, verified, failed, and adherence rate for the
     * authenticated representative.
     */
    public function stats(Request $request): JsonResponse
    {
        $rep = $request->user()->scientificRep;

        if (! $rep) {
            return response()->json(['message' => 'Representative profile not found.'], 404);
        }

        $total = RepresentativeVisit::where('rep_id', $rep->id)->count();
        $verified = RepresentativeVisit::where('rep_id', $rep->id)->where('verification_status', true)->count();

        return response()->json([
            'data' => new RepStatsResource([
                'total_visits' => $total,
                'verified_visits' => $verified,
                'failed_visits' => max(0, $total - $verified),
                'adherence_rate' => $total > 0 ? round(($verified / $total) * 100, 2) : 0,
            ]),
        ]);
    }

    private function distanceInMeters(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($toLatitude - $fromLatitude);
        $lngDelta = deg2rad($toLongitude - $fromLongitude);
        $fromLat = deg2rad($fromLatitude);
        $toLat = deg2rad($toLatitude);

        $a = sin($latDelta / 2) ** 2
            + cos($fromLat) * cos($toLat) * sin($lngDelta / 2) ** 2;

        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}
