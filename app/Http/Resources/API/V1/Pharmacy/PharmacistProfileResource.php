<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Pharmacy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PharmacistProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'f_name' => $this->f_name,
            'l_name' => $this->l_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'age' => $this->age,
            'gender' => $this->gender,
            'location' => $this->location,
            'pharmacist' => [
                'id' => $this->pharmacist->id,
                'verification_status' => $this->pharmacist->verification_status,
                'syndicate_card' => $this->pharmacist->syndicate_card
                    ? asset('storage/'.$this->pharmacist->syndicate_card)
                    : null,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
