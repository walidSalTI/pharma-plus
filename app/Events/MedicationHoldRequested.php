<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\MedicationOrder;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Log;
use Throwable;

class MedicationHoldRequested implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public MedicationOrder $order,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('pharmacy.'.$this->order->pharmacy_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'medication.hold.requested';
    }

    public function broadcastWith(): array
    {
        // Safely resolve patient name even if relationships are empty
        $patient = $this->order->patient;
        $user = $patient?->user;
        $patientName = $user ? ($user->f_name.' '.$user->l_name) : 'Walk-in Customer';

        try {
            return [
                'order_id' => $this->order->id,
                'invoice_number' => $this->order->invoice_number,
                'patient_name' => $patientName,
                'source' => $this->order->source,
                'total_price' => (float) $this->order->total_price,
                'status' => $this->order->status,
                'items' => $this->order->items->map(fn ($item) => [
                    'medication_id' => $item->medication_id,
                    'trade_name' => $item->medication?->product?->name ?? 'N/A',
                    'quantity' => $item->quantity,
                    'price' => (float) $item->price,
                ]),
                'created_at' => $this->order->created_at?->toISOString(),
            ];
        } catch (Throwable $e) {
            Log::error('Broadcasting failed in MedicationHoldRequested: '.$e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }
}
