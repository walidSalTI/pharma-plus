<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\MedicationOrder;
use App\Services\InventoryService;

class MedicationOrderObserver
{
    protected array $stockActiveStates = ['confirmed', 'ready', 'completed'];

    public function __construct(protected InventoryService $inventoryService) {}

    public function updated(MedicationOrder $order): void
    {
        if (! $order->isDirty('status')) {
            return;
        }

        $newStatus = $order->status;
        $oldStatus = $order->getOriginal('status');

        // Entering an active stock state from a non-active state -> move stock
        if (in_array($newStatus, $this->stockActiveStates, true) && ! in_array($oldStatus, $this->stockActiveStates, true)) {
            $this->handleStockMovement($order);
        }

        // Cancelling after stock was active -> restock
        if ($newStatus === 'cancelled' && in_array($oldStatus, $this->stockActiveStates, true)) {
            $this->handleCancellationRestock($order);
        }
    }

    protected function handleStockMovement(MedicationOrder $order): void
    {
        // Outbound movements (decrement stock)
        if (in_array($order->type, ['sale', 'damaged', 'supplier_return'], true)) {
            $this->inventoryService->decrementStock($order);
        }

        // Inbound movements (increment stock)
        if (in_array($order->type, ['purchase', 'customer_return'], true)) {
            $this->inventoryService->incrementStock($order);
        }
    }

    protected function handleCancellationRestock(MedicationOrder $order): void
    {
        // Reverse outbound (restock)
        if (in_array($order->type, ['sale', 'damaged', 'supplier_return'], true)) {
            $this->inventoryService->incrementStock($order);
        }

        // Reverse inbound (re-decrement)
        if (in_array($order->type, ['purchase', 'customer_return'], true)) {
            $this->inventoryService->decrementStock($order);
        }
    }
}
