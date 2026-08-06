<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Pharmacy\BulkImportInventoryRequest;
use App\Http\Requests\API\V1\Pharmacy\StoreInventoryRequest;
use App\Http\Requests\API\V1\Pharmacy\UpdateInventoryRequest;
use App\Http\Resources\API\V1\Pharmacy\InventoryResource;
use App\Models\Pharmacy;
use App\Models\PharmacyInventory;
use App\Models\PharmacyInventoryBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryController extends Controller
{
    public function index(Request $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manageInventory', $pharmacy);

        $inventory = $pharmacy->pharmacyInventories()
            ->with('medication')
            ->when($request->filled('low_stock'), fn ($q) => $q->whereColumn('stock', '<=', 'min_stock'))
            ->when($request->filled('medication_id'), fn ($q) => $q->where('medication_id', $request->medication_id))
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json([
            'data' => InventoryResource::collection($inventory),
            'meta' => [
                'current_page' => $inventory->currentPage(),
                'last_page' => $inventory->lastPage(),
                'per_page' => $inventory->perPage(),
                'total' => $inventory->total(),
            ],
        ]);
    }

    public function store(StoreInventoryRequest $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manageInventory', $pharmacy);

        $validated = $request->validated();
        $created = [];
        $skipped = [];

        foreach ($validated['items'] as $item) {
            $exists = $pharmacy->pharmacyInventories()
                ->where('medication_id', $item['medication_id'])
                ->exists();

            if ($exists) {
                $skipped[] = [
                    'medication_id' => $item['medication_id'],
                    'message' => 'Already in inventory. Use PUT to update.',
                ];

                continue;
            }

            $inventory = PharmacyInventory::create([
                'pharmacy_id' => $pharmacy->id,
                'medication_id' => $item['medication_id'],
                'price' => $item['price'],
                'stock' => 0,
                'min_stock' => $item['min_stock'] ?? 10,
                'last_updated' => now(),
            ]);

            if (isset($item['wholesale_price'], $item['stock'], $item['expiration_date'])) {
                $batchNumber = $item['batch_number'] ?? 'BATCH-'.strtoupper(Str::random(8));

                PharmacyInventoryBatch::create([
                    'pharmacy_inventory_id' => $inventory->id,
                    'batch_number' => $batchNumber,
                    'quantity' => $item['stock'],
                    'wholesale_price' => $item['wholesale_price'],
                    'expiration_date' => $item['expiration_date'],
                ]);

                $inventory->syncStock();
            }

            $inventory->load('medication');
            $created[] = new InventoryResource($inventory);
        }

        return response()->json([
            'message' => count($created) > 0
                ? count($created).' inventory item(s) added successfully.'
                : 'No items were added.',
            'data' => $created,
            'skipped' => $skipped,
        ], count($created) > 0 ? 201 : 200);
    }

    public function update(UpdateInventoryRequest $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manageInventory', $pharmacy);

        $validated = $request->validated();
        $updated = [];
        $notFound = [];

        foreach ($validated['items'] as $item) {
            $inventory = $pharmacy->pharmacyInventories()
                ->where('medication_id', $item['medication_id'])
                ->first();

            if (! $inventory) {
                $notFound[] = [
                    'medication_id' => $item['medication_id'],
                    'message' => 'Medication not found in inventory.',
                ];

                continue;
            }

            $updateData = ['last_updated' => now()];

            if (isset($item['price'])) {
                $updateData['price'] = $item['price'];
            }
            if (isset($item['min_stock'])) {
                $updateData['min_stock'] = $item['min_stock'];
            }

            $inventory->update($updateData);

            $inventory->load('medication');
            $updated[] = new InventoryResource($inventory);
        }

        return response()->json([
            'message' => count($updated).' inventory item(s) updated successfully.',
            'data' => $updated,
            'not_found' => $notFound,
        ]);
    }

    public function updateSingle(Request $request, Pharmacy $pharmacy, PharmacyInventory $inventory): JsonResponse
    {
        if ($inventory->pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Inventory item not found for this pharmacy.'], 404);
        }

        $this->authorize('manageInventory', $pharmacy);

        $validated = $request->validate([
            'price' => ['nullable', 'numeric', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
        ]);

        $updateData = ['last_updated' => now()];

        if (isset($validated['price'])) {
            $updateData['price'] = $validated['price'];
        }
        if (isset($validated['min_stock'])) {
            $updateData['min_stock'] = $validated['min_stock'];
        }

        $inventory->update($updateData);

        $inventory->load('medication');

        return response()->json([
            'message' => 'Inventory item updated successfully.',
            'data' => new InventoryResource($inventory),
        ]);
    }

    // public function incrementStock(Request $request, Pharmacy $pharmacy, PharmacyInventory $inventory): JsonResponse
    // {
    //     if ($inventory->pharmacy_id !== $pharmacy->id) {
    //         return response()->json(['message' => 'Inventory item not found for this pharmacy.'], 404);
    //     }

    //     $this->authorize('manageInventory', $pharmacy);

    //     $validated = $request->validate([
    //         'quantity' => ['required', 'integer', 'min:1'],
    //         'batch_id' => ['nullable', 'string', 'exists:pharmacy_inventory_batches,id'],
    //         'batch_number' => ['nullable', 'string', 'max:255'],
    //         'wholesale_price' => ['nullable', 'numeric', 'min:0'],
    //         'expiration_date' => ['nullable', 'date', 'after_or_equal:today'],
    //     ]);

    //     if (isset($validated['batch_id'])) {
    //         $batch = PharmacyInventoryBatch::where('id', $validated['batch_id'])
    //             ->where('pharmacy_inventory_id', $inventory->id)
    //             ->first();

    //         if (! $batch) {
    //             return response()->json(['message' => 'Batch not found for this inventory item.'], 404);
    //         }

    //         $batch->increment('quantity', $validated['quantity']);
    //     } else {
    //         $batchNumber = $validated['batch_number'] ?? 'BATCH-'.strtoupper(Str::random(8));

    //         PharmacyInventoryBatch::create([
    //             'pharmacy_inventory_id' => $inventory->id,
    //             'batch_number' => $batchNumber,
    //             'quantity' => $validated['quantity'],
    //             'wholesale_price' => $validated['wholesale_price'] ?? 0,
    //             'expiration_date' => $validated['expiration_date'] ?? now()->addYear()->toDateString(),
    //         ]);
    //     }

    //     $inventory->syncStock();
    //     $inventory->load('medication');

    //     return response()->json([
    //         'message' => "Stock increased by {$validated['quantity']}.",
    //         'data' => new InventoryResource($inventory),
    //     ]);
    // }

    // public function decrementStock(Request $request, Pharmacy $pharmacy, PharmacyInventory $inventory): JsonResponse
    // {
    //     if ($inventory->pharmacy_id !== $pharmacy->id) {
    //         return response()->json(['message' => 'Inventory item not found for this pharmacy.'], 404);
    //     }

    //     $this->authorize('manageInventory', $pharmacy);

    //     $validated = $request->validate([
    //         'quantity' => ['required', 'integer', 'min:1'],
    //     ]);

    //     if ($inventory->stock < $validated['quantity']) {
    //         return response()->json([
    //             'message' => 'Insufficient stock. Requested quantity exceeds available stock.',
    //             'available_stock' => $inventory->stock,
    //         ], 400);
    //     }

    //     DB::transaction(function () use ($validated, $inventory) {
    //         $remaining = $validated['quantity'];

    //         $batches = $inventory->activeBatches()->lockForUpdate()->get();

    //         foreach ($batches as $batch) {
    //             if ($remaining <= 0) {
    //                 break;
    //             }

    //             $take = min($remaining, $batch->quantity);
    //             $batch->decrement('quantity', $take);
    //             $remaining -= $take;
    //         }

    //         $inventory->syncStock();
    //     });

    //     $inventory->load('medication');

    //     return response()->json([
    //         'message' => "Stock decreased by {$validated['quantity']}.",
    //         'data' => new InventoryResource($inventory),
    //     ]);
    // }

    public function destroy(Request $request, Pharmacy $pharmacy, PharmacyInventory $inventory): JsonResponse
    {
        if ($inventory->pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Inventory item not found for this pharmacy.'], 404);
        }

        $this->authorize('manageInventory', $pharmacy);

        $hasStock = $inventory->batches()->where('quantity', '>', 0)->exists();

        if ($hasStock) {
            return response()->json([
                'message' => 'Cannot delete inventory item with remaining stock. Remove all batches first.',
            ], 400);
        }

        $inventory->delete();

        return response()->json(['message' => 'Inventory item removed successfully.']);
    }

    public function lowStock(Request $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manageInventory', $pharmacy);

        $lowStockItems = $pharmacy->pharmacyInventories()
            ->with('medication')
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock', 'asc')
            ->get();

        return response()->json([
            'data' => InventoryResource::collection($lowStockItems),
            'meta' => [
                'total' => $lowStockItems->count(),
            ],
        ]);
    }

    public function bulkImport(BulkImportInventoryRequest $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manageInventory', $pharmacy);

        $file = $request->file('file');

        $filePath = $file->store('bulk-imports', 'local');

        return response()->json([
            'message' => 'File uploaded successfully. Processing will be completed asynchronously.',
            'data' => [
                'file_path' => $filePath,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ],
        ]);
    }
}
