<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientLedgerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'blood_type' => $this->blood_type,
            'chronic_records' => $this->whenLoaded('chronicRecords', fn () => $this->chronicRecords->map(fn ($record) => [
                'id' => $record->id,
                'disease_name_en' => $record->chronicDisease?->name_en,
                'disease_name_ar' => $record->chronicDisease?->name_ar,
                'diagnosis_year' => $record->diagnosis_year,
                'severity' => $record->severity,
                'created_at' => $record->created_at,
            ])),
            'active_medications' => $this->whenLoaded('medicationPatients', fn () => $this->medicationPatients->where('is_active', true)->values()->map(fn ($mp) => [
                'id' => $mp->id,
                'medication_id' => $mp->medication_id,
                'trade_name' => $mp->medication?->product?->name,
                'dosage' => $mp->dosage,
                'frequency' => $mp->frequency,
                'state' => $mp->state,
                'start_date' => $mp->start_date,
                'end_date' => $mp->end_date,
                'instructions_before' => $mp->instructions_before,
                'instructions_after' => $mp->instructions_after,
            ])),
            'order_history' => $this->whenLoaded('medicationOrders', fn () => $this->medicationOrders->map(fn ($order) => [
                'id' => $order->id,
                'pharmacy_name' => $order->pharmacy?->name,
                'total_price' => $order->total_price,
                'status' => $order->status,
                'invoice_number' => $order->invoice_number,
                'created_at' => $order->created_at,
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
