<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Pharmacy\StoreBatchRequest;
use App\Http\Requests\API\V1\Pharmacy\UpdateBatchRequest;
use App\Http\Resources\API\V1\Pharmacy\InventoryBatchResource;
use App\Models\Pharmacy;
use App\Models\PharmacyInventory;
use App\Models\PharmacyInventoryBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function index(Request $request, Pharmacy $pharmacy, PharmacyInventory $inventory): JsonResponse
    {
        if ($inventory->pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Inventory item not found for this pharmacy.'], 404);
        }

        $this->authorize('manageInventory', $pharmacy);

        $batches = $inventory->batches()
            ->orderBy('expiration_date', 'asc')
            ->paginate($request->input('per_page', 50));

        return response()->json([
            'data' => InventoryBatchResource::collection($batches),
            'meta' => [
                'current_page' => $batches->currentPage(),
                'last_page' => $batches->lastPage(),
                'per_page' => $batches->perPage(),
                'total' => $batches->total(),
            ],
        ]);
    }

    public function store(StoreBatchRequest $request, Pharmacy $pharmacy, PharmacyInventory $inventory): JsonResponse
    {
        if ($inventory->pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Inventory item not found for this pharmacy.'], 404);
        }

        $this->authorize('manageInventory', $pharmacy);

        $validated = $request->validated();

        $batchNumber = $validated['batch_number'] ?? 'BATCH-'.strtoupper(uniqid());

        $batch = PharmacyInventoryBatch::create([
            'pharmacy_inventory_id' => $inventory->id,
            'batch_number' => $batchNumber,
            'quantity' => $validated['quantity'],
            'wholesale_price' => $validated['wholesale_price'],
            'expiration_date' => $validated['expiration_date'],
        ]);

        $inventory->syncStock();

        return response()->json([
            'message' => 'Batch created successfully.',
            'data' => new InventoryBatchResource($batch),
        ], 201);
    }

    public function show(Pharmacy $pharmacy, PharmacyInventory $inventory, PharmacyInventoryBatch $batch): JsonResponse
    {
        if ($inventory->pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Inventory item not found for this pharmacy.'], 404);
        }

        if ($batch->pharmacy_inventory_id !== $inventory->id) {
            return response()->json(['message' => 'Batch not found for this inventory item.'], 404);
        }

        $this->authorize('manageInventory', $pharmacy);

        return response()->json([
            'data' => new InventoryBatchResource($batch),
        ]);
    }

    public function update(UpdateBatchRequest $request, Pharmacy $pharmacy, PharmacyInventory $inventory, PharmacyInventoryBatch $batch): JsonResponse
    {
        if ($inventory->pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Inventory item not found for this pharmacy.'], 404);
        }

        if ($batch->pharmacy_inventory_id !== $inventory->id) {
            return response()->json(['message' => 'Batch not found for this inventory item.'], 404);
        }

        $this->authorize('manageInventory', $pharmacy);

        $validated = $request->validated();

        $batch->update($validated);

        $inventory->syncStock();

        return response()->json([
            'message' => 'Batch updated successfully.',
            'data' => new InventoryBatchResource($batch),
        ]);
    }

    public function destroy(Pharmacy $pharmacy, PharmacyInventory $inventory, PharmacyInventoryBatch $batch): JsonResponse
    {
        if ($inventory->pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Inventory item not found for this pharmacy.'], 404);
        }

        if ($batch->pharmacy_inventory_id !== $inventory->id) {
            return response()->json(['message' => 'Batch not found for this inventory item.'], 404);
        }

        $this->authorize('manageInventory', $pharmacy);

        if ($batch->quantity > 0) {
            return response()->json([
                'message' => 'Cannot delete a batch with remaining stock. Decrement stock to zero first.',
            ], 400);
        }

        $batch->delete();

        $inventory->syncStock();

        return response()->json(['message' => 'Batch deleted successfully.']);
    }
}
