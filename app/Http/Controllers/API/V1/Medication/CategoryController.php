<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Medication;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Medication\CategoryResource;
use App\Http\Resources\API\V1\Medication\TitleResource;
use App\Http\Resources\API\V1\Medication\UsageResource;
use App\Models\Category;
use App\Models\Title;
use Illuminate\Http\JsonResponse;

/**
 * Medication Hierarchy Dropdown Endpoints.
 *
 * Provides cascading filter data for the medication catalog UI.
 * The frontend uses these to populate Category → Title → Usage dropdowns.
 */
class CategoryController extends Controller
{
    /**
     * List all medication categories.
     *
     * GET /api/v1/categories
     */
    public function index(): JsonResponse
    {
        $categories = Category::orderBy('name')->get();

        return response()->json([
            'data' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * List titles (subcategories) for a given category.
     *
     * GET /api/v1/categories/{id}/titles
     */
    public function titles(string $category_id): JsonResponse
    {
        logger($category_id);
        $titles = Title::where('category_id', $category_id)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => TitleResource::collection($titles),
        ]);
    }

    /**
     * List usages for a given title.
     *
     * GET /api/v1/titles/{id}/usages
     */
    public function usages(string $title_id): JsonResponse
    {
        logger($title_id);
        $title = Title::where('id', $title_id)->first();

        $usages = $title->usages()
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => UsageResource::collection($usages),
        ]);
    }
}
