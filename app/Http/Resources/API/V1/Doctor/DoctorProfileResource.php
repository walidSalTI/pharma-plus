<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Doctor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'specialization' => $this->specialization,
            'syndicate_card_image' => $this->syndicate_card_image ? asset('storage/'.$this->syndicate_card_image) : null,
            'verification_status' => $this->verification_status,
            'user' => [
                'id' => $this->user?->id,
                'f_name' => $this->user?->f_name,
                'l_name' => $this->user?->l_name,
                'email' => $this->user?->email,
                'phone_number' => $this->user?->phone_number,
                'age' => $this->user?->age,
                'gender' => $this->user?->gender,
                'location' => $this->user?->location,
            ],
            'workplaces' => WorkplaceResource::collection($this->whenLoaded('doctorWorkplaces')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
