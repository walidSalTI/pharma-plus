<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Company\StoreAssignmentRequest;
use App\Http\Resources\API\V1\Company\AssignmentResource;
use App\Models\DoctorAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentController extends Controller
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
     * List doctor assignments.
     *
     * Return the active company's rep-to-doctor assignments with names.
     */
    public function index(Request $request): JsonResponse
    {
        $company = $this->activeCompany($request);
        $assignments = $company->doctorAssignments()
            ->with(['scientificRep.user', 'doctor.user'])
            ->latest()
            ->paginate(25);

        return response()->json([
            'data' => AssignmentResource::collection($assignments),
            'meta' => [
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage(),
                'per_page' => $assignments->perPage(),
                'total' => $assignments->total(),
            ],
        ]);
    }

    /**
     * Store doctor assignment.
     *
     * Ensure the rep belongs to the company, prevent duplicate assignment,
     * and create the doctor assignment row.
     */
    public function store(StoreAssignmentRequest $request): JsonResponse
    {
        $company = $this->activeCompany($request);
        $validated = $request->validated();

        if (! $company->scientificReps()->whereKey($validated['rep_id'])->exists()) {
            return response()->json(['message' => 'Representative not found for this company.'], 422);
        }

        $assignment = DoctorAssignment::firstOrCreate([
            'company_id' => $company->id,
            'rep_id' => $validated['rep_id'],
            'doctor_id' => $validated['doctor_id'],
        ])->load(['scientificRep.user', 'doctor.user']);

        return response()->json([
            'message' => 'Doctor assignment saved successfully.',
            'data' => new AssignmentResource($assignment),
        ], 201);
    }

    /**
     * Delete doctor assignment.
     *
     * Confirm company ownership, then delete the assignment row.
     */
    public function destroy(Request $request, DoctorAssignment $assignment): JsonResponse
    {
        $company = $this->activeCompany($request);
        if ($assignment->company_id !== $company->id) {
            return response()->json(['message' => 'Assignment not found.'], 404);
        }

        $assignment->delete();

        return response()->json(['message' => 'Doctor assignment deleted successfully.']);
    }
}
