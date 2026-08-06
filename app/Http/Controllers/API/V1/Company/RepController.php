<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Company\StoreRepRequest;
use App\Http\Resources\API\V1\Company\RepDetailResource;
use App\Http\Resources\API\V1\Company\RepResource;
use App\Models\ScientificRep;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepController extends Controller
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
     * List company representatives.
     *
     * Ensure the owner has an active company and return all reps with users.
     */
    public function index(Request $request): JsonResponse
    {
        $company = $this->activeCompany($request);
        $reps = $company->scientificReps()->with('user')->latest()->paginate(25);

        return response()->json([
            'data' => RepResource::collection($reps),
            'meta' => [
                'current_page' => $reps->currentPage(),
                'last_page' => $reps->lastPage(),
                'per_page' => $reps->perPage(),
                'total' => $reps->total(),
            ],
        ]);
    }

    /**
     * Store representative.
     *
     * Create User and ScientificRep in one transaction, assign scientific_rep
     * role, and return the new representative profile.
     */
    public function store(StoreRepRequest $request): JsonResponse
    {
        $company = $this->activeCompany($request);
        $validated = $request->validated();

        $rep = DB::transaction(function () use ($company, $validated): ScientificRep {
            $user = User::create([
                'f_name' => $validated['f_name'],
                'l_name' => $validated['l_name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'phone_number' => $validated['phone_number'],
                'age' => $validated['age'],
                'gender' => $validated['gender'],
                'location' => $validated['location'] ?? null,
                'email_verified_at' => now(),
            ]);
            $user->assignRole('scientific_rep');

            return ScientificRep::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
            ])->load('user');
        });

        return response()->json([
            'message' => 'Representative created successfully.',
            'data' => new RepResource($rep),
        ], 201);
    }

    /**
     * Show representative.
     *
     * Confirm ownership and include assignment, schedule, and visit counts.
     */
    public function show(Request $request, ScientificRep $rep): JsonResponse
    {
        $company = $this->activeCompany($request);
        if ($rep->company_id !== $company->id) {
            return response()->json(['message' => 'Representative not found.'], 404);
        }

        $rep->load('user')
            ->loadCount(['doctorAssignments', 'weeklySchedules', 'representativeVisits'])
            ->loadCount(['representativeVisits as verified_visits_count' => fn ($query) => $query->where('verification_status', true)]);

        return response()->json(['data' => new RepDetailResource($rep)]);
    }

    /**
     * Suspend representative.
     *
     * Confirm ownership, revoke all current tokens, and remove field role.
     */
    public function suspend(Request $request, ScientificRep $rep): JsonResponse
    {
        $company = $this->activeCompany($request);
        if ($rep->company_id !== $company->id) {
            return response()->json(['message' => 'Representative not found.'], 404);
        }

        $rep->load('user');
        $rep->user->tokens()->delete();
        $rep->user->removeRole('scientific_rep');

        return response()->json(['message' => 'Representative suspended successfully.']);
    }

    /**
     * Activate representative.
     *
     * Confirm ownership and restore the scientific_rep role.
     */
    public function activate(Request $request, ScientificRep $rep): JsonResponse
    {
        $company = $this->activeCompany($request);
        if ($rep->company_id !== $company->id) {
            return response()->json(['message' => 'Representative not found.'], 404);
        }

        $rep->load('user');
        $rep->user->assignRole('scientific_rep');

        return response()->json(['message' => 'Representative activated successfully.']);
    }

    /**
     * Delete representative.
     *
     * Confirm ownership and delete the representative user, cascading the rep row.
     */
    public function destroy(Request $request, ScientificRep $rep): JsonResponse
    {
        $company = $this->activeCompany($request);
        if ($rep->company_id !== $company->id) {
            return response()->json(['message' => 'Representative not found.'], 404);
        }

        $rep->load('user');
        $rep->user->delete();

        return response()->json(['message' => 'Representative deleted successfully.']);
    }
}
