<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Admin\StoreMedicationRequest;
use App\Http\Requests\API\V1\Admin\UpdateMedicationRequest;
use App\Http\Requests\API\V1\Specialist\StoreActiveIngredientRequest;
use App\Http\Requests\API\V1\Specialist\StoreChronicDiseaseRequest;
use App\Http\Requests\API\V1\Specialist\UpdateActiveIngredientRequest;
use App\Http\Requests\API\V1\Specialist\UpdateChronicDiseaseRequest;
use App\Http\Resources\API\V1\Admin\MedicationResource;
use App\Http\Resources\API\V1\Specialist\ActiveIngredientResource;
use App\Http\Resources\API\V1\Specialist\ChronicDiseaseResource;
use App\Models\ActiveIngredient;
use App\Models\ChronicDisease;
use App\Models\Medication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicalDataController extends Controller
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

    public function medications(Request $request): JsonResponse
    {
        $medications = Medication::with('manufacture', 'activeIngredients', 'usage.title.category', 'product')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('category_id'), fn ($q) => $q->whereHas('usage.title', fn ($q) => $q->where('category_id', $request->input('category_id'))))
            ->when($request->filled('title_id'), fn ($q) => $q->whereHas('usage', fn ($q) => $q->where('title_id', $request->input('title_id'))))
            ->when($request->filled('usage_id'), fn ($q) => $q->where('usage_id', $request->input('usage_id')))
            ->when($request->filled('category'), fn ($q) => $q->whereHas('usage.title.category', fn ($q) => $q->where('name', 'like', '%'.$request->input('category').'%')))
            ->when($request->filled('title'), fn ($q) => $q->whereHas('usage.title', fn ($q) => $q->where('name', 'like', '%'.$request->input('title').'%')))
            ->when($request->filled('usage'), fn ($q) => $q->whereHas('usage', fn ($q) => $q->where('name', 'like', '%'.$request->input('usage').'%')))
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => MedicationResource::collection($medications),
            'meta' => [
                'current_page' => $medications->currentPage(),
                'last_page' => $medications->lastPage(),
                'per_page' => $medications->perPage(),
                'total' => $medications->total(),
            ],
        ]);
    }

    public function storeMedication(StoreMedicationRequest $request): JsonResponse
    {
        $medication = Medication::create($request->validated());

        return response()->json([
            'message' => 'Medication created successfully.',
            'data' => new MedicationResource($medication->load('manufacture', 'activeIngredients', 'usage.title.category', 'product')),
        ], 201);
    }

    public function updateMedication(UpdateMedicationRequest $request, Medication $medication): JsonResponse
    {
        $medication->update($request->validated());

        return response()->json([
            'message' => 'Medication updated successfully.',
            'data' => new MedicationResource($medication->fresh()->load('manufacture', 'activeIngredients', 'usage.title.category', 'product')),
        ]);
    }

    public function destroyMedication(Medication $medication): JsonResponse
    {
        $medication->delete();

        return response()->json(['message' => 'Medication deleted successfully.']);
    }
}
