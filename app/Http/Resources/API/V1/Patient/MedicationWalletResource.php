<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicationWalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'medication_id' => $this->medication_id,
            'trade_name' => $this->medication?->product?->name,
            'form' => $this->medication?->form,
            'medication_image' => $this->medication?->product?->image ? asset('storage/'.$this->medication->product->image) : null,
            'category' => $this->medication?->usage?->title?->category ? [
                'id' => $this->medication->usage->title->category->id,
                'name' => $this->medication->usage->title->category->name,
            ] : null,
            'title' => $this->medication?->usage?->title ? [
                'id' => $this->medication->usage->title->id,
                'name' => $this->medication->usage->title->name,
            ] : null,
            'usage' => $this->medication?->usage ? [
                'id' => $this->medication->usage->id,
                'name' => $this->medication->usage->name,
            ] : null,
            'state' => $this->state,
            'chronic_id' => $this->chronic_id,
            'dosage' => $this->dosage,
            'available_pills' => $this->available_pills,
            'frequency' => $this->frequency,
            'refill_risk' => $this->refill_risk,
            'instructions_before' => $this->instructions_before,
            'instructions_after' => $this->instructions_after,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_active' => $this->is_active,
            'schedules' => MedicationScheduleResource::collection($this->whenLoaded('medicationSchedules')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
