<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Company\VisitExportRequest;
use App\Http\Resources\API\V1\Company\DashboardStatsResource;
use App\Http\Resources\API\V1\Company\VisitResource;
use App\Models\RepresentativeVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitController extends Controller
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
     * List visits.
     *
     * Return visits made by reps owned by the active company with optional
     * rep, doctor, verification, and date filters.
     */
    public function index(Request $request): JsonResponse
    {
        $company = $this->activeCompany($request);
        $repIds = $company->scientificReps()->pluck('id');

        $visits = RepresentativeVisit::with(['scientificRep.user', 'doctor.user'])
            ->whereIn('rep_id', $repIds)
            ->when($request->filled('rep_id'), fn ($query) => $query->where('rep_id', $request->input('rep_id')))
            ->when($request->filled('doctor_id'), fn ($query) => $query->where('doctor_id', $request->input('doctor_id')))
            ->when($request->filled('verification_status'), fn ($query) => $query->where('verification_status', (bool) $request->boolean('verification_status')))
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
     * Show visit.
     *
     * Confirm the visit belongs to a company rep and return visit details.
     */
    public function show(Request $request, RepresentativeVisit $visit): JsonResponse
    {
        $company = $this->activeCompany($request);
        if (! $company->scientificReps()->whereKey($visit->rep_id)->exists()) {
            return response()->json(['message' => 'Visit not found.'], 404);
        }

        return response()->json([
            'data' => new VisitResource($visit->load(['scientificRep.user', 'doctor.user'])),
        ]);
    }

    /**
     * Export visits.
     *
     * Return filtered visit records in JSON. CSV/PDF serialization can be
     * layered later without changing the query contract.
     */
    public function export(VisitExportRequest $request): JsonResponse
    {
        $company = $this->activeCompany($request);
        $validated = $request->validated();
        $repIds = $company->scientificReps()->pluck('id');

        $visits = RepresentativeVisit::with(['scientificRep.user', 'doctor.user'])
            ->whereIn('rep_id', $repIds)
            ->when(isset($validated['from']), fn ($query) => $query->whereDate('scanned_at', '>=', $validated['from']))
            ->when(isset($validated['to']), fn ($query) => $query->whereDate('scanned_at', '<=', $validated['to']))
            ->latest('scanned_at')
            ->get();

        return response()->json([
            'format' => $validated['format'] ?? 'json',
            'data' => VisitResource::collection($visits),
        ]);
    }

    /**
     * Show visit stats.
     *
     * Aggregate reps, assignments, schedules, verified visits, failed visits,
     * and adherence rate for the active company dashboard.
     */
    public function stats(Request $request): JsonResponse
    {
        $company = $this->activeCompany($request);
        $repIds = $company->scientificReps()->pluck('id');
        $totalVisits = RepresentativeVisit::whereIn('rep_id', $repIds)->count();
        $verifiedVisits = RepresentativeVisit::whereIn('rep_id', $repIds)->where('verification_status', true)->count();

        return response()->json([
            'data' => new DashboardStatsResource([
                'total_reps' => $company->scientificReps()->count(),
                'total_assignments' => $company->doctorAssignments()->count(),
                'total_schedules' => $company->scientificReps()->withCount('weeklySchedules')->get()->sum('weekly_schedules_count'),
                'completed_visits' => $totalVisits,
                'verified_visits' => $verifiedVisits,
                'failed_visits' => max(0, $totalVisits - $verifiedVisits),
                'adherence_rate' => $totalVisits > 0 ? round(($verifiedVisits / $totalVisits) * 100, 2) : 0,
            ]),
        ]);
    }
}
