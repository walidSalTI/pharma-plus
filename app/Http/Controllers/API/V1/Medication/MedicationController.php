<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Medication;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Medication\MedicationResource;
use App\Models\Medication;
use Illuminate\Http\Request;

/**
 * Global Medication Catalog (FR-P-3).
 *
 * Public endpoints for fetching and filtering the global medication
 * catalog. Available to all user roles without authentication.
 */
class MedicationController extends Controller
{
    /**
     * List & filter medications (FR-P-3.1, FR-P-3.2).
     *
     * Returns a paginated list of medications filtered by optional
     * query parameters. Supports partial matching on trade name,
     * active ingredient name, and manufacturer name.
     *
     * @queryParam name string Partial match on trade_name.
     * @queryParam active_ingredient string Partial match on active_ingredients.ingredient_name_en.
     * @queryParam company string Partial match on manufacture name.
     */
    public function index(Request $request): mixed
    {
        $medicines = Medication::query()
            ->where('status', 'accepted')
            ->with(['manufacture', 'medicationIngredients.activeIngredient', 'usage.title.category', 'product'])
            ->when($request->filled('name'), fn ($q) => $q->whereHas('product', fn ($q) => $q->where('name', 'like', '%'.$request->input('name').'%')))
            ->when($request->filled('active_ingredient'), fn ($q) => $q->whereHas('activeIngredients', fn ($q) => $q->where('ingredient_name_en', 'like', '%'.$request->input('active_ingredient').'%')))
            ->when($request->filled('company'), fn ($q) => $q->whereHas('manufacture', fn ($q) => $q->where('name', 'like', '%'.$request->input('company').'%')))
            ->when($request->filled('category_id'), fn ($q) => $q->whereHas('usage.title', fn ($q) => $q->where('category_id', $request->input('category_id'))))
            ->when($request->filled('title_id'), fn ($q) => $q->whereHas('usage', fn ($q) => $q->where('title_id', $request->input('title_id'))))
            ->when($request->filled('usage_id'), fn ($q) => $q->where('usage_id', $request->input('usage_id')))
            ->when($request->filled('category'), fn ($q) => $q->whereHas('usage.title.category', fn ($q) => $q->where('name', 'like', '%'.$request->input('category').'%')))
            ->when($request->filled('title'), fn ($q) => $q->whereHas('usage.title', fn ($q) => $q->where('name', 'like', '%'.$request->input('title').'%')))
            ->when($request->filled('usage'), fn ($q) => $q->whereHas('usage', fn ($q) => $q->where('name', 'like', '%'.$request->input('usage').'%')))
            ->paginate(30);

        return MedicationResource::collection($medicines);
    }
}
