<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Specialist;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Specialist\StoreRiskMappingRequest;
use App\Http\Requests\API\V1\Specialist\UpdateRiskMappingRequest;
use App\Http\Resources\API\V1\Specialist\RiskMappingResource;
use App\Models\ActiveIngredient;
use App\Models\ActiveIngredientsChronicDisease;
use App\Models\ChronicDisease;
use Illuminate\Http\JsonResponse;

class RiskMappingController extends Controller
{
    public function index(): JsonResponse
    {
        $mappings = ActiveIngredientsChronicDisease::with(['chronicDisease', 'activeIngredient'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => RiskMappingResource::collection($mappings),
            'meta' => [
                'current_page' => $mappings->currentPage(),
                'last_page' => $mappings->lastPage(),
                'per_page' => $mappings->perPage(),
                'total' => $mappings->total(),
            ],
        ]);
    }

    public function store(StoreRiskMappingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $exists = ActiveIngredientsChronicDisease::where('chronic_disease_id', $validated['chronic_disease_id'])
            ->where('active_ingredient_id', $validated['active_ingredient_id'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'This risk mapping already exists.'], 422);
        }

        $mapping = ActiveIngredientsChronicDisease::create($validated);

        return response()->json([
            'message' => 'Risk mapping created successfully.',
            'data' => new RiskMappingResource($mapping->load(['chronicDisease', 'activeIngredient'])),
        ], 201);
    }

    public function update(UpdateRiskMappingRequest $request, ActiveIngredientsChronicDisease $mapping): JsonResponse
    {
        $mapping->update($request->validated());

        return response()->json([
            'message' => 'Risk mapping updated successfully.',
            'data' => new RiskMappingResource($mapping->fresh()->load(['chronicDisease', 'activeIngredient'])),
        ]);
    }

    public function destroy(ActiveIngredientsChronicDisease $mapping): JsonResponse
    {
        $mapping->delete();

        return response()->json(['message' => 'Risk mapping deleted successfully.']);
    }

    public function chronicDiseases(): JsonResponse
    {
        return response()->json([
            'data' => ChronicDisease::select('id', 'name_en', 'name_ar')->orderBy('name_en')->get(),
        ]);
    }

    public function activeIngredients(): JsonResponse
    {
        return response()->json([
            'data' => ActiveIngredient::select('id', 'ingredient_name_en')->orderBy('ingredient_name_en')->get(),
        ]);
    }
}
