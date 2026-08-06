<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorVerificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'specialization' => $this->specialization,
            'syndicate_card_image' => $this->syndicate_card_image ? asset('storage/'.$this->syndicate_card_image) : null,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'f_name' => $this->user->f_name,
                'l_name' => $this->user->l_name,
                'email' => $this->user->email,
                'phone_number' => $this->user->phone_number,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
