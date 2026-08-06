<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Rep;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'doctor_id' => $this->doctor_id,
            'schedule_id' => $this->schedule_id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'scanned_at' => $this->scanned_at,
            'verification_status' => (bool) $this->verification_status,
            'notes' => $this->notes ?? null,
            'doctor_name' => $this->whenLoaded('doctor', fn () => trim($this->doctor->user->f_name.' '.$this->doctor->user->l_name)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
