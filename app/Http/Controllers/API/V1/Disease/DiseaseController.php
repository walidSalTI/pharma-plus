<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Disease;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Disease\StoreDiseaseRequest;
use App\Http\Requests\API\V1\Disease\UpdateDiseaseRequest;
use App\Http\Resources\API\V1\Disease\DiseaseResource;
use App\Models\ChronicDisease;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Global Chronic Disease Catalog.
 *
 * Public read endpoints for fetching the global chronic diseases catalog,
 * and admin-only endpoints for managing the catalog (CRUD).
 *
 * Flow (index):
 * 1. Apply optional filters: name_ar, name_en, code, category.
 * 2. Paginate results (30 per page by default).
 * 3. Return paginated DiseaseResource collection.
 *
 * Flow (store):
 * 1. Validate input via StoreDiseaseRequest.
 * 2. Create a new ChronicDisease record.
 * 3. Return the new record wrapped in DiseaseResource.
 *
 * Flow (update):
 * 1. Validate input via UpdateDiseaseRequest.
 * 2. Update the ChronicDisease record with only provided fields.
 * 3. Return the updated record wrapped in DiseaseResource.
 *
 * Flow (destroy):
 * 1. Delete the ChronicDisease record (cascading handled by DB).
 */
class DiseaseController extends Controller
{
    /**
     * List & filter global chronic diseases (public).
     *
     * Returns a paginated list of chronic diseases from the global catalog.
     * Supports optional filtering by name (Arabic/English), code, and category.
     * No authentication required — available to all users.
     *
     * @queryParam name_ar string Partial match on Arabic name.
     * @queryParam name_en string Partial match on English name.
     * @queryParam code string Partial match on disease code.
     * @queryParam category string Exact match on category.
     * @queryParam per_page int Items per page (default: 30).
     */
    public function index(Request $request): JsonResponse
    {
        $diseases = ChronicDisease::query()
            ->when($request->filled('name_ar'), fn ($q) => $q->where('name_ar', 'like', '%'.$request->input('name_ar').'%'))
            ->when($request->filled('name_en'), fn ($q) => $q->where('name_en', 'like', '%'.$request->input('name_en').'%'))
            ->when($request->filled('code'), fn ($q) => $q->where('code', 'like', '%'.$request->input('code').'%'))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->input('category')))
            ->orderBy('name_en')
            ->paginate(min((int) ($request->input('per_page', 30)), 100));

        return response()->json(DiseaseResource::collection($diseases)->response()->getData(true));
    }

    /**
     * Get a single chronic disease by ID (public).
     *
     * Returns a single chronic disease with its linked active ingredients
     * and risk levels. No authentication required.
     */
    public function show(ChronicDisease $chronicDisease): JsonResponse
    {
        $chronicDisease->load('activeIngredients');

        return response()->json([
            'data' => new DiseaseResource($chronicDisease),
        ]);
    }

    /**
     * Create a new chronic disease (admin only).
     *
     * Requires authentication with the admin role.
     * Creates a new entry in the global chronic_diseases catalog.
     */
    public function store(StoreDiseaseRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $disease = ChronicDisease::create([
            'code' => $validated['code'] ?? null,
            'name_ar' => $validated['name_ar'],
            'name_en' => $validated['name_en'],
            'category' => $validated['category'] ?? null,
        ]);

        return response()->json([
            'message' => 'Chronic disease created successfully.',
            'data' => new DiseaseResource($disease),
        ], 201);
    }

    /**
     * Update an existing chronic disease (admin only).
     *
     * Requires authentication with the admin role.
     * Only the provided fields will be updated.
     */
    public function update(UpdateDiseaseRequest $request, ChronicDisease $chronicDisease): JsonResponse
    {
        $validated = $request->validated();

        $chronicDisease->update([
            'code' => array_key_exists('code', $validated) ? $validated['code'] : $chronicDisease->code,
            'name_ar' => $validated['name_ar'] ?? $chronicDisease->name_ar,
            'name_en' => $validated['name_en'] ?? $chronicDisease->name_en,
            'category' => array_key_exists('category', $validated) ? $validated['category'] : $chronicDisease->category,
        ]);

        return response()->json([
            'message' => 'Chronic disease updated successfully.',
            'data' => new DiseaseResource($chronicDisease),
        ]);
    }

    /**
     * Delete a chronic disease (admin only).
     *
     * Requires authentication with the admin role.
     * Permanently removes the disease and cascades to related records.
     */
    public function destroy(ChronicDisease $chronicDisease): JsonResponse
    {
        $chronicDisease->delete();

        return response()->json([
            'message' => 'Chronic disease deleted successfully.',
        ]);
    }
}
