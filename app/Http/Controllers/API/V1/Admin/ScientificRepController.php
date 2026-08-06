<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Admin\StoreScientificRepRequest;
use App\Http\Requests\API\V1\Admin\UpdateScientificRepRequest;
use App\Http\Resources\API\V1\Admin\AdminScientificRepResource;
use App\Models\ScientificRep;
use Illuminate\Http\JsonResponse;

class ScientificRepController extends Controller
{
    public function index(): JsonResponse
    {
        $reps = ScientificRep::with('user', 'pharmaceuticalCompany')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => AdminScientificRepResource::collection($reps),
            'meta' => [
                'current_page' => $reps->currentPage(),
                'last_page' => $reps->lastPage(),
                'per_page' => $reps->perPage(),
                'total' => $reps->total(),
            ],
        ]);
    }

    public function show(ScientificRep $scientificRep): JsonResponse
    {
        return response()->json([
            'data' => new AdminScientificRepResource($scientificRep->load('user', 'pharmaceuticalCompany')),
        ]);
    }

    public function store(StoreScientificRepRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $rep = ScientificRep::create([
            'user_id' => $validated['user_id'],
            'company_id' => $validated['company_id'],
        ]);

        return response()->json([
            'message' => 'Scientific rep created successfully.',
            'data' => new AdminScientificRepResource($rep->load('user', 'pharmaceuticalCompany')),
        ], 201);
    }

    public function update(UpdateScientificRepRequest $request, ScientificRep $scientificRep): JsonResponse
    {
        $scientificRep->update($request->validated());

        return response()->json([
            'message' => 'Scientific rep updated successfully.',
            'data' => new AdminScientificRepResource($scientificRep->fresh()->load('user', 'pharmaceuticalCompany')),
        ]);
    }

    public function destroy(ScientificRep $scientificRep): JsonResponse
    {
        $scientificRep->user->delete();

        return response()->json(['message' => 'Scientific rep deleted successfully.']);
    }
}
