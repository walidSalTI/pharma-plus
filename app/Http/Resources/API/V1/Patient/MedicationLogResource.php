<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicationLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'schedule_id' => $this->schedule_id,
            'dose_time' => $this->medicationSchedule?->dose_time,
            'medication_name' => $this->medicationSchedule?->medicationPatient?->medication?->product?->name,
            'status' => $this->status,
            'reason' => $this->reason,
            'taken_at' => $this->taken_at,
            'created_at' => $this->created_at,
        ];
    }
}
