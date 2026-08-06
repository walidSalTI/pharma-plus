<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicationScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dose_time' => $this->dose_time,
            'day_of_week' => $this->day_of_week,
            'created_at' => $this->created_at,
        ];
    }
}
