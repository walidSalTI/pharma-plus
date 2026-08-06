<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Specialist;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Specialist\StoreActiveIngredientRequest;
use App\Http\Requests\API\V1\Specialist\StoreChronicDiseaseRequest;
use App\Http\Requests\API\V1\Specialist\UpdateActiveIngredientRequest;
use App\Http\Requests\API\V1\Specialist\UpdateChronicDiseaseRequest;
use App\Http\Resources\API\V1\Specialist\ActiveIngredientResource;
use App\Http\Resources\API\V1\Specialist\ChronicDiseaseResource;
use App\Models\ActiveIngredient;
use App\Models\ChronicDisease;
use Illuminate\Http\JsonResponse;

class KnowledgeBaseController extends Controller
{
    public function chronicDiseases(): JsonResponse
    {
        $diseases = ChronicDisease::latest()->paginate(20);

        return response()->json([
            'data' => ChronicDiseaseResource::collection($diseases),
            'meta' => [
                'current_page' => $diseases->currentPage(),
                'last_page' => $diseases->lastPage(),
                'per_page' => $diseases->perPage(),
                'total' => $diseases->total(),
            ],
        ]);
    }

    public function storeChronicDisease(StoreChronicDiseaseRequest $request): JsonResponse
    {
        $disease = ChronicDisease::create($request->validated());

        return response()->json([
            'message' => 'Chronic disease created successfully.',
            'data' => new ChronicDiseaseResource($disease),
        ], 201);
    }

    public function updateChronicDisease(UpdateChronicDiseaseRequest $request, ChronicDisease $chronicDisease): JsonResponse
    {
        $chronicDisease->update($request->validated());

        return response()->json([
            'message' => 'Chronic disease updated successfully.',
            'data' => new ChronicDiseaseResource($chronicDisease->fresh()),
        ]);
    }

    public function destroyChronicDisease(ChronicDisease $chronicDisease): JsonResponse
    {
        $chronicDisease->delete();

        return response()->json(['message' => 'Chronic disease deleted successfully.']);
    }

    public function activeIngredients(): JsonResponse
    {
        $ingredients = ActiveIngredient::latest()->paginate(20);

        return response()->json([
            'data' => ActiveIngredientResource::collection($ingredients),
            'meta' => [
                'current_page' => $ingredients->currentPage(),
                'last_page' => $ingredients->lastPage(),
                'per_page' => $ingredients->perPage(),
                'total' => $ingredients->total(),
            ],
        ]);
    }

    public function storeActiveIngredient(StoreActiveIngredientRequest $request): JsonResponse
    {
        $ingredient = ActiveIngredient::create($request->validated());

        return response()->json([
            'message' => 'Active ingredient created successfully.',
            'data' => new ActiveIngredientResource($ingredient),
        ], 201);
    }

    public function updateActiveIngredient(UpdateActiveIngredientRequest $request, ActiveIngredient $activeIngredient): JsonResponse
    {
        $activeIngredient->update($request->validated());

        return response()->json([
            'message' => 'Active ingredient updated successfully.',
            'data' => new ActiveIngredientResource($activeIngredient->fresh()),
        ]);
    }

    public function destroyActiveIngredient(ActiveIngredient $activeIngredient): JsonResponse
    {
        $activeIngredient->delete();

        return response()->json(['message' => 'Active ingredient deleted successfully.']);
    }
}
