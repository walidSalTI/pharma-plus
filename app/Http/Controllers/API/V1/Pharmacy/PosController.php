<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Pharmacy\FindPosItemRequest;
use App\Http\Requests\API\V1\Pharmacy\StorePosSaleRequest;
use App\Http\Requests\API\V1\Pharmacy\StorePurchaseRequest;
use App\Models\MedicationOrder;
use App\Models\Pharmacy;
use App\Models\PharmacyInventory;
use App\Models\PharmacyInventoryBatch;
use App\Services\InventoryService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
    ) {}

    public function findItem(FindPosItemRequest $request, Pharmacy $pharmacy): JsonResponse
    {
        $search = $request->query('search');

        $inventoryItem = PharmacyInventory::with(['medication.product', 'activeBatches'])
            ->where('pharmacy_id', $pharmacy->id)
            ->whereHas('medication.product', function ($query) use ($search) {
                $query->where('barcode', $search)
                    ->orWhere('name', 'like', "%{$search}%");
            })
            ->first();

        if (! $inventoryItem) {
            return response()->json([
                'message' => 'Medication not found in this pharmacy inventory or barcode is invalid.',
            ], 404);
        }

        $batches = $inventoryItem->activeBatches->map(fn ($batch) => [
            'id' => $batch->id,
            'batch_number' => $batch->batch_number,
            'quantity' => $batch->quantity,
            'wholesale_price' => (float) $batch->wholesale_price,
            'expiration_date' => $batch->expiration_date->toDateString(),
        ]);

        return response()->json([
            'medication_id' => $inventoryItem->medication_id,
            'inventory_id' => $inventoryItem->id,
            'name' => $inventoryItem->medication->product?->name,
            'barcode' => $inventoryItem->medication->product?->barcode,
            'price' => (float) $inventoryItem->price,
            'available_stock' => $inventoryItem->stock,
            'batches' => $batches,
        ]);
    }

    public function store(StorePosSaleRequest $request, Pharmacy $pharmacy): JsonResponse
    {
        $validated = $request->validated();
        $pharmacist = $request->user()->pharmacist;

        try {
            $order = DB::transaction(function () use ($validated, $pharmacy, $pharmacist) {

                $totalPrice = collect($validated['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_price']);

                $invoiceNumber = 'INV-POS-'.strtoupper(Str::random(10));

                $order = MedicationOrder::create([
                    'pharmacy_id' => $pharmacy->id,
                    'pharmacist_id' => $pharmacist?->id,
                    'type' => 'sale',
                    'source' => 'POS',
                    'status' => 'completed',
                    'total_price' => $totalPrice,
                    'invoice_number' => $invoiceNumber,
                ]);

                foreach ($validated['items'] as $item) {
                    $order->items()->create([
                        'medication_id' => $item['medication_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['unit_price'],
                        'batch_id' => $item['batch_id'] ?? null,
                    ]);
                }

                $order->load('items');

                $this->inventoryService->decrementStock($order);

                return $order;
            });

            $order->refresh();

            return response()->json([
                'message' => 'POS sale completed successfully.',
                'data' => [
                    'order_id' => $order->id,
                    'invoice_number' => $order->invoice_number,
                    'total_price' => (float) $order->total_price,
                    'total_cost' => (float) $order->total_cost,
                    'gross_profit' => (float) ($order->total_price - $order->total_cost),
                    'status' => $order->status,
                    'source' => $order->source,
                    'created_at' => $order->created_at,
                ],
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Sale failed: '.$e->getMessage(),
            ], 422);
        }
    }

    public function purchase(StorePurchaseRequest $request, Pharmacy $pharmacy): JsonResponse
    {
        $validated = $request->validated();
        $pharmacist = $request->user()->pharmacist;

        try {
            $order = DB::transaction(function () use ($validated, $pharmacy, $pharmacist) {

                $totalCost = collect($validated['items'])->sum(
                    fn ($item) => $item['quantity'] * $item['wholesale_price']
                );

                $invoiceNumber = 'PUR-'.strtoupper(Str::random(10));

                $notes = "Supplier: {$validated['supplier_name']}";
                if (! empty($validated['notes'])) {
                    $notes .= " | Notes: {$validated['notes']}";
                }

                $order = MedicationOrder::create([
                    'pharmacy_id' => $pharmacy->id,
                    'pharmacist_id' => $pharmacist?->id,
                    'type' => 'purchase',
                    'source' => 'POS',
                    'status' => 'completed',
                    'total_price' => $totalCost,
                    'total_cost' => $totalCost,
                    'supplier_name' => $validated['supplier_name'],
                    'invoice_number' => $invoiceNumber,
                    'notes' => $notes,
                ]);

                foreach ($validated['items'] as $item) {

                    $inventory = PharmacyInventory::firstOrCreate(
                        [
                            'pharmacy_id' => $pharmacy->id,
                            'medication_id' => $item['medication_id'],
                        ],
                        [
                            'stock' => 0,
                            'price' => $item['wholesale_price'],
                        ]
                    );

                    $existingBatch = PharmacyInventoryBatch::where('pharmacy_inventory_id', $inventory->id)
                        ->whereDate('expiration_date', $item['expiration_date'])
                        ->first();

                    if ($existingBatch) {
                        $existingBatch->increment('quantity', $item['quantity']);
                        $existingBatch->update([
                            'wholesale_price' => $item['wholesale_price'],
                        ]);

                        $batch = $existingBatch;
                    } else {
                        $batchNumber = $item['batch_number'] ?? 'BATCH-'.strtoupper(Str::random(8));

                        $batch = PharmacyInventoryBatch::create([
                            'pharmacy_inventory_id' => $inventory->id,
                            'batch_number' => $batchNumber,
                            'quantity' => $item['quantity'],
                            'wholesale_price' => $item['wholesale_price'],
                            'expiration_date' => $item['expiration_date'],
                        ]);
                    }

                    $order->items()->create([
                        'medication_id' => $item['medication_id'],
                        'batch_id' => $batch->id,
                        'quantity' => $item['quantity'],
                        'price' => $item['wholesale_price'],
                        'wholesale_price_at_sale' => $item['wholesale_price'],
                    ]);

                    $inventory->syncStock();
                }

                return $order->load('items');
            });

            return response()->json([
                'message' => 'Purchase order recorded successfully.',
                'data' => [
                    'order_id' => $order->id,
                    'invoice_number' => $order->invoice_number,
                    'supplier_name' => $order->supplier_name,
                    'total_cost' => (float) $order->total_price,
                    'items_count' => $order->items->count(),
                    'created_at' => $order->created_at,
                ],
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Recording purchase order failed: '.$e->getMessage(),
            ], 422);
        }
    }

    public function recordDamaged(Request $request, Pharmacy $pharmacy): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:550',
            'items' => 'required|array|min:1',
            'items.*.medication_id' => 'required|exists:medications,id',
            'items.*.batch_id' => 'nullable|exists:pharmacy_inventory_batches,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $pharmacist = $request->user()->pharmacist;

        try {
            $order = DB::transaction(function () use ($validated, $pharmacy, $pharmacist) {

                $invoiceNumber = 'DMG-'.strtoupper(Str::random(10));

                $order = MedicationOrder::create([
                    'pharmacy_id' => $pharmacy->id,
                    'pharmacist_id' => $pharmacist?->id,
                    'type' => 'damaged',
                    'source' => 'POS',
                    'status' => 'completed',
                    'total_price' => 0,
                    'invoice_number' => $invoiceNumber,
                    'notes' => $validated['notes'] ?? 'Damaged item reported at POS counter',
                ]);

                foreach ($validated['items'] as $item) {
                    $order->items()->create([
                        'medication_id' => $item['medication_id'],
                        'batch_id' => $item['batch_id'] ?? null,
                        'quantity' => $item['quantity'],
                        'price' => 0,
                    ]);
                }

                $order->load('items');

                $this->inventoryService->decrementStock($order);

                return $order;
            });

            $order->refresh();

            return response()->json([
                'message' => 'Damaged items recorded and removed from inventory successfully.',
                'data' => [
                    'order_id' => $order->id,
                    'total_loss' => (float) $order->total_cost,
                    'created_at' => $order->created_at,
                ],
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Recording damaged items failed: '.$e->getMessage(),
            ], 422);
        }
    }

    public function reverseDamage(Request $request, Pharmacy $pharmacy): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.medication_id' => 'required|exists:medications,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.wholesale_price' => 'nullable|numeric|min:0',
            'items.*.batch_id' => 'nullable|string|exists:pharmacy_inventory_batches,id',
            'notes' => 'nullable|string|max:255',
        ]);

        $pharmacist = $request->user()->pharmacist;

        try {
            $order = DB::transaction(function () use ($validated, $pharmacy, $pharmacist) {

                foreach ($validated['items'] as $index => $item) {
                    if (empty($item['batch_id']) && empty($item['wholesale_price'])) {
                        throw new Exception("wholesale_price is required when batch_id is not provided for item {$index}.");
                    }
                }

                $returnItems = [];
                foreach ($validated['items'] as $item) {
                    $wholesalePrice = $item['wholesale_price'] ?? null;

                    if (empty($wholesalePrice) && ! empty($item['batch_id'])) {
                        $batch = PharmacyInventoryBatch::findOrFail($item['batch_id']);
                        $wholesalePrice = $batch->wholesale_price;
                    }

                    $returnItems[] = [
                        'medication_id' => $item['medication_id'],
                        'quantity' => $item['quantity'],
                        'price' => 0,
                        'wholesale_price_at_sale' => $wholesalePrice ?? 0,
                        'batch_id' => $item['batch_id'] ?? null,
                    ];
                }

                $totalCost = collect($returnItems)->sum(fn ($i) => $i['quantity'] * $i['wholesale_price_at_sale']);

                $order = MedicationOrder::create([
                    'pharmacy_id' => $pharmacy->id,
                    'pharmacist_id' => $pharmacist?->id,
                    'type' => 'damage_reversal',
                    'source' => 'POS',
                    'status' => 'completed',
                    'total_price' => 0,
                    'total_cost' => $totalCost,
                    'invoice_number' => 'DMG-REV-'.strtoupper(Str::random(10)),
                    'notes' => $validated['notes'] ?? 'Damage reversal — restocking damaged items',
                ]);

                foreach ($returnItems as $item) {
                    $order->items()->create($item);
                }

                $order->load('items');

                $this->inventoryService->incrementStock($order);

                return $order;
            });

            return response()->json([
                'message' => 'Damage reversal recorded successfully.',
                'data' => [
                    'order_id' => $order->id,
                    'invoice_number' => $order->invoice_number,
                    'total_price' => 0,
                    'total_cost' => (float) $order->total_cost,
                    'items' => $order->items,
                    'created_at' => $order->created_at,
                ],
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to reverse damage: '.$e->getMessage(),
            ], 422);
        }
    }

    public function returnSale(Request $request, Pharmacy $pharmacy): JsonResponse
    {
        $validated = $request->validate([
            'original_invoice_number' => 'nullable|string|exists:medication_orders,invoice_number',
            'items' => 'nullable|array',
            'items.*.medication_id' => 'required_with:items|exists:medications,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.wholesale_price' => 'required_without:original_invoice_number|nullable|numeric|min:0',
            'items.*.batch_id' => 'nullable|string|exists:pharmacy_inventory_batches,id',
        ]);

        if (empty($validated['original_invoice_number']) && empty($validated['items'])) {
            return response()->json(['message' => 'Either original_invoice_number or items must be provided.'], 422);
        }

        $pharmacist = $request->user()->pharmacist;

        try {
            $order = DB::transaction(function () use ($validated, $pharmacy, $pharmacist) {

                $returnItems = [];
                $originalOrder = null;

                if (! empty($validated['original_invoice_number'])) {
                    $originalOrder = MedicationOrder::with('items')
                        ->where('invoice_number', $validated['original_invoice_number'])
                        ->where('pharmacy_id', $pharmacy->id)
                        ->firstOrFail();

                    if (! empty($validated['items'])) {
                        foreach ($validated['items'] as $itemInput) {
                            $originalItem = $originalOrder->items
                                ->where('medication_id', $itemInput['medication_id'])
                                ->first();

                            if (! $originalItem) {
                                throw new Exception("Medication ID {$itemInput['medication_id']} was not part of original invoice.");
                            }

                            $returnItems[] = [
                                'medication_id' => $itemInput['medication_id'],
                                'quantity' => $itemInput['quantity'],
                                'price' => $itemInput['unit_price'] ?? $originalItem->price,
                                'wholesale_price_at_sale' => $originalItem->wholesale_price_at_sale,
                                'batch_id' => $itemInput['batch_id'] ?? $originalItem->batch_id,
                            ];
                        }
                    } else {
                        foreach ($originalOrder->items as $originalItem) {
                            $returnItems[] = [
                                'medication_id' => $originalItem->medication_id,
                                'quantity' => $originalItem->quantity,
                                'price' => $originalItem->price,
                                'wholesale_price_at_sale' => $originalItem->wholesale_price_at_sale,
                                'batch_id' => $originalItem->batch_id,
                            ];
                        }
                    }
                } else {
                    foreach ($validated['items'] as $itemInput) {
                        $returnItems[] = [
                            'medication_id' => $itemInput['medication_id'],
                            'quantity' => $itemInput['quantity'],
                            'price' => $itemInput['unit_price'],
                            'wholesale_price_at_sale' => $itemInput['wholesale_price'],
                            'batch_id' => $itemInput['batch_id'] ?? null,
                        ];
                    }
                }

                $totalRefundPrice = collect($returnItems)->sum(fn ($i) => $i['quantity'] * $i['price']);
                $totalCost = collect($returnItems)->sum(fn ($i) => $i['quantity'] * $i['wholesale_price_at_sale']);
                $invoiceNumber = 'RET-'.strtoupper(Str::random(10));

                $returnOrder = MedicationOrder::create([
                    'pharmacy_id' => $pharmacy->id,
                    'pharmacist_id' => $pharmacist?->id,
                    'type' => 'customer_return',
                    'source' => 'POS',
                    'status' => 'completed',
                    'total_price' => $totalRefundPrice,
                    'total_cost' => $totalCost,
                    'invoice_number' => $invoiceNumber,
                    'notes' => $originalOrder
                        ? 'Return for invoice: '.$originalOrder->invoice_number
                        : 'Direct POS Return',
                ]);

                foreach ($returnItems as $item) {
                    $returnOrder->items()->create($item);
                }

                if ($originalOrder) {
                    $originalOrder->update(['is_returned' => true]);
                }

                $returnOrder->load('items');

                $this->inventoryService->incrementStock($returnOrder);

                return $returnOrder;
            });

            return response()->json([
                'message' => 'Return processed successfully.',
                'data' => [
                    'order_id' => $order->id,
                    'invoice_number' => $order->invoice_number,
                    'total_price' => (float) $order->total_price,
                    'total_cost' => (float) $order->total_cost,
                    'status' => $order->status,
                    'type' => $order->type,
                    'source' => $order->source,
                    'items' => $order->items,
                    'created_at' => $order->created_at,
                ],
            ], 201);

        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
