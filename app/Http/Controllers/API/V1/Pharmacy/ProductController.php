<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Pharmacy\StoreProductRequest;
use App\Http\Requests\API\V1\Pharmacy\UpdateProductRequest;
use App\Http\Resources\API\V1\Pharmacy\ProductResource;
use App\Models\Pharmacy;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manageInventory', $pharmacy);

        $products = Product::where('added_by_pharmacy_id', $pharmacy->id)
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function store(StoreProductRequest $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manageInventory', $pharmacy);

        $product = Product::create([
            ...$request->validated(),
            'added_by_pharmacy_id' => $pharmacy->id,
        ]);

        return response()->json([
            'message' => 'Product created successfully.',
            'data' => new ProductResource($product),
        ], 201);
    }

    public function show(Pharmacy $pharmacy, Product $product): JsonResponse
    {
        $this->authorize('manageInventory', $pharmacy);

        if ($product->added_by_pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        return response()->json([
            'data' => new ProductResource($product),
        ]);
    }

    public function update(UpdateProductRequest $request, Pharmacy $pharmacy, Product $product): JsonResponse
    {
        $this->authorize('manageInventory', $pharmacy);

        if ($product->added_by_pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $product->update($request->validated());

        return response()->json([
            'message' => 'Product updated successfully.',
            'data' => new ProductResource($product->fresh()),
        ]);
    }

    public function destroy(Request $request, Pharmacy $pharmacy, Product $product): JsonResponse
    {
        $this->authorize('manageInventory', $pharmacy);

        if ($product->added_by_pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully.']);
    }
}
