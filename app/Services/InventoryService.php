<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MedicationOrder;
use App\Models\PharmacyInventory;
use App\Models\PharmacyInventoryBatch;
use Exception;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Decrement stock using FEFO (First Expired, First Out).
     *
     * Each order item is fulfilled from the nearest-expiring active batches.
     * If a single item spans multiple batches, the original MedicationOrderItem
     * row is updated for the first batch and new rows are created for each
     * additional batch (order item splitting).
     *
     * The summary stock column on pharmacy_inventories is synced after all
     * items are processed.
     *
     * @throws Exception if insufficient stock for any medication
     */
    public function decrementStock(MedicationOrder $order): void
    {

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {

                // 🟢 حالة 1: الصيدلي حدد Batch معين بنفسه (مثل حالة الضرر أو المرتجع المباشر)
                if ($item->batch_id) {
                    // 1️⃣ الخطوة الأولى: جلب وقفل سجل المخزون الرئيسي أولاً
                    $inventory = PharmacyInventory::where('pharmacy_id', $order->pharmacy_id)
                        ->where('medication_id', $item->medication_id)
                        ->lockForUpdate()
                        ->first();
                    // 2️⃣ الخطوة الثانية: جلب وقفل الـ Batch المحددة مباشرة بـ id المخزون
                    $batch = PharmacyInventoryBatch::where('id', $item->batch_id)
                        ->where('pharmacy_inventory_id', $inventory->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $batch || $batch->quantity < $item->quantity) {
                        $available = $batch ? $batch->quantity : 0;
                        throw new Exception(
                            "Insufficient stock in the selected batch (ID: {$item->batch_id}). "
                                ."Available: {$available}, requested: {$item->quantity}."
                        );
                    }

                    // خصم الكمية مباشرة من الـ Batch المحددة
                    $batch->decrement('quantity', $item->quantity);

                    // حفظ سعر الجملة للدفعة المحددة لحساب التكلفة/الخسارة بدقة
                    $item->update([
                        'wholesale_price_at_sale' => $batch->wholesale_price,
                    ]);
                } else {
                    // 🔵 حالة 2: الخصم التلقائي حسب FEFO (البيع العادي في الـ POS)
                    $inventory = PharmacyInventory::where('pharmacy_id', $order->pharmacy_id)
                        ->where('medication_id', $item->medication_id)
                        ->lockForUpdate()
                        ->first();
                    if (! $inventory) {
                        throw new Exception(
                            "Inventory not found for medication (ID: {$item->medication_id}) in this pharmacy."
                        );
                    }

                    $batches = PharmacyInventoryBatch::where('pharmacy_inventory_id', $inventory->id)
                        ->where('quantity', '>', 0)
                        ->orderBy('expiration_date', 'asc')
                        ->lockForUpdate()
                        ->get();

                    $totalAvailable = $batches->sum('quantity');
                    if ($totalAvailable < $item->quantity) {
                        throw new Exception(
                            "Insufficient stock for medication (ID: {$item->medication_id}). "
                                ."Available: {$totalAvailable}, requested: {$item->quantity}."
                        );
                    }

                    $remaining = $item->quantity;
                    $firstBatch = true;

                    foreach ($batches as $batch) {
                        if ($remaining <= 0) {
                            break;
                        }

                        $take = min($remaining, $batch->quantity);

                        $batch->decrement('quantity', $take);

                        $remaining -= $take;

                        if ($firstBatch) {
                            $item->update([
                                'batch_id' => $batch->id,
                                'wholesale_price_at_sale' => $batch->wholesale_price,
                                'quantity' => $take,
                            ]);
                            $firstBatch = false;
                        } else {
                            $order->items()->create([
                                'medication_id' => $item->medication_id,
                                'quantity' => $take,
                                'price' => $item->price,
                                'batch_id' => $batch->id,
                                'wholesale_price_at_sale' => $batch->wholesale_price,
                            ]);
                        }
                    }
                }

                // تحديث التكلفة الإجمالية للطلب/الخسارة
                $order->load('items');
                $totalCost = $order->items->sum(fn ($i) => $i->quantity * ($i->wholesale_price_at_sale ?? 0));
                DB::table('medication_orders')
                    ->where('id', $order->id)
                    ->update(['total_cost' => $totalCost]);

                // تحديث مجموع المخزون الموحد
                $inventory = PharmacyInventory::where('pharmacy_id', $order->pharmacy_id)
                    ->where('medication_id', $item->medication_id)
                    ->first();

                if ($inventory) {
                    $inventory->syncStock();
                }
            }
        });
    }

    /**
     * Increment stock on return (customer_return) or restock (purchase).
     *
     * Batch resolution strategy:
     *   1. If the order item has a batch_id, attach to that specific batch.
     *   2. Fallback: attach to the most recent active batch for the medication.
     *   3. If no active batches exist, create a new batch (auto-generated
     *      batch_number, expiration one year from now).
     *
     * The summary stock column on pharmacy_inventories is synced after all
     * items are processed.
     */
    public function incrementStock(MedicationOrder $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                // 1️⃣ جلب وقفل سجل المخزون الرئيسي
                $inventory = PharmacyInventory::where('pharmacy_id', $order->pharmacy_id)
                    ->where('medication_id', $item->medication_id)
                    ->lockForUpdate()
                    ->first();

                if (! $inventory) {
                    $inventory = PharmacyInventory::create([
                        'pharmacy_id' => $order->pharmacy_id,
                        'medication_id' => $item->medication_id,
                        'stock' => 0,
                        'price' => 0,
                    ]);
                }

                $batch = null;

                // 2️⃣ المحاولة الأولى: جلب الدفعة المحددة وقفلها
                if ($item->batch_id) {
                    $batch = PharmacyInventoryBatch::where('id', $item->batch_id)
                        ->where('pharmacy_inventory_id', $inventory->id)
                        ->lockForUpdate()
                        ->first();
                }

                // 3️⃣ المحاولة الثانية: جلب أقرب دفعة نشطة حسب FEFO وقفلها
                if (! $batch) {
                    $batch = $inventory->activeBatches()
                        ->orderBy('expiration_date', 'asc')
                        ->lockForUpdate()
                        ->first();
                }

                // 4️⃣ المحاولة الثالثة: إنشاء دفعة جديدة إن لم توجد أي دفعة نشطة
                if (! $batch) {
                    $batch = PharmacyInventoryBatch::create([
                        'pharmacy_inventory_id' => $inventory->id,
                        'quantity' => 0,
                        'wholesale_price' => $item->wholesale_price_at_sale ?? 0,
                        'expiration_date' => now()->addYear(),
                    ]);
                }

                // زيادة الكمية وتحديث إجمالي المخزون
                $batch->increment('quantity', $item->quantity);
                $inventory->syncStock();
            }
        });
    }
}
