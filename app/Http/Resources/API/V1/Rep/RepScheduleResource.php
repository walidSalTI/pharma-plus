<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Rep;

use App\Http\Resources\API\V1\Doctor\WorkplaceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RepScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'doctor_id' => $this->doctor_id,
            'scheduled_at' => $this->scheduled_at,
            'notes' => $this->notes,
            'status' => $this->status,
            'doctor' => $this->whenLoaded('doctor', fn () => [
                'id' => $this->doctor->id,
                'name' => trim($this->doctor->user->f_name.' '.$this->doctor->user->l_name),
                'specialization' => $this->doctor->specialization,
                'workplaces' => $this->when(
                    $this->doctor?->relationLoaded('doctorWorkplaces'),
                    fn () => WorkplaceResource::collection($this->doctor->doctorWorkplaces),
                ),
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
