<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Pharmacy;


use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Pharmacy\ListOrdersRequest;
use App\Http\Requests\API\V1\Pharmacy\UpdateOrderStatusRequest;
use App\Models\MedicationOrder;
use App\Models\Pharmacy;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function index(ListOrdersRequest $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('viewDashboard', $pharmacy);

        $validated = $request->validated();

        $query = MedicationOrder::with([
            'items.medication.product',
            'patient.user',
            'pharmacist.user',
        ])->where('pharmacy_id', $pharmacy->id);

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (! empty($validated['source'])) {
            $query->where('source', $validated['source']);
        }

        if (! empty($validated['invoice_number'])) {
            $query->where('invoice_number', $validated['invoice_number']);
        }

        if (isset($validated['is_returned'])) {
            $query->where('is_returned', $validated['is_returned']);
        }

        if (! empty($validated['min_price'])) {
            $query->where('total_price', '>=', $validated['min_price']);
        }

        if (! empty($validated['max_price'])) {
            $query->where('total_price', '<=', $validated['max_price']);
        }

        if (! empty($validated['min_cost'])) {
            $query->where('total_cost', '>=', $validated['min_cost']);
        }

        if (! empty($validated['max_cost'])) {
            $query->where('total_cost', '<=', $validated['max_cost']);
        }

        if (! empty($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (! empty($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        if (! empty($validated['pharmacist_name'])) {
            $name = $validated['pharmacist_name'];
            $query->whereHas('pharmacist.user', function ($userQ) use ($name) {
                $userQ->where('f_name', 'like', "%{$name}%")
                    ->orWhere('l_name', 'like', "%{$name}%");
            });
        }

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('patient.user', function ($userQ) use ($search) {
                        $userQ->where('f_name', 'like', "%{$search}%")
                            ->orWhere('l_name', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = min((int) ($validated['per_page'] ?? 20), 100);
        $orders = $query->orderByDesc('created_at')->paginate($perPage);

        $data = $orders->map(fn (MedicationOrder $order) => [
            'order_id' => $order->id,
            'invoice_number' => $order->invoice_number,
            'status' => $order->status,
            'source' => $order->source,
            'type' => $order->type,
            'total_price' => (float) $order->total_price,
            'total_cost' => (float) $order->total_cost,
            'is_returned' => $order->is_returned,
            'supplier_name' => $order->supplier_name,
            'pharmacist_note' => $order->pharmacist_note,
            'notes' => $order->notes,
            'patient_name' => $order->patient?->user
                ? trim($order->patient->user->f_name.' '.$order->patient->user->l_name)
                : null,
            'pharmacist_name' => $order->pharmacist?->user
                ? trim($order->pharmacist->user->f_name.' '.$order->pharmacist->user->l_name)
                : null,
            'items_count' => $order->items->count(),
            'items' => $order->items->map(fn ($item) => [
                'medication_id' => $item->medication_id,
                'trade_name' => $item->medication?->product?->name,
                'quantity' => $item->quantity,
                'price' => (float) $item->price,
                'wholesale_price_at_sale' => $item->wholesale_price_at_sale !== null
                    ? (float) $item->wholesale_price_at_sale
                    : null,
                'batch_id' => $item->batch_id,
            ]),
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    private const VALID_TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['ready', 'cancelled'],
        'ready' => ['completed', 'cancelled'],
    ];

    public function updateStatus(UpdateOrderStatusRequest $request, string $pharmacy, string $order): JsonResponse
    {
        $validated = $request->validated();

        $order = MedicationOrder::with('items')->find($order);

        if (! $order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        $this->authorize('processOrders', $order->pharmacy);

        $newStatus = $validated['status'];
        $oldStatus = $order->status;

        if ($oldStatus === 'cancelled') {
            return response()->json(['message' => 'Cannot update a cancelled order.'], 422);
        }

        if ($oldStatus === 'completed') {
            return response()->json(['message' => 'Cannot update a completed order.'], 422);
        }

        if ($oldStatus === $newStatus) {
            return response()->json(['message' => "Order is already {$oldStatus}."], 422);
        }

        $allowed = self::VALID_TRANSITIONS[$oldStatus] ?? [];
        if (! in_array($newStatus, $allowed, true)) {
            return response()->json([
                'message' => "Invalid transition from {$oldStatus} to {$newStatus}.",
            ], 422);
        }

        $updateData = ['status' => $newStatus];

        if ($order->pharmacist_id === null) {
            $pharmacist = $request->user()->pharmacist;
            if ($pharmacist) {
                $updateData['pharmacist_id'] = $pharmacist->id;
            }
        }

        $order->update($updateData);
        $order->refresh();

        return response()->json([
            'message' => 'Order status updated successfully.',
            'data' => [
                'order_id' => $order->id,
                'invoice_number' => $order->invoice_number,
                'status' => $order->status,
                'total_price' => $order->total_price,
                'total_cost' => (float) $order->total_cost,
                'pharmacy_id' => $order->pharmacy_id,
                'pharmacist_id' => $order->pharmacist_id,
                'updated_at' => $order->updated_at,
            ],
        ]);
    }
}
