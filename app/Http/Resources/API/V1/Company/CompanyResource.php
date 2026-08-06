<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Company;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'commercial_name' => $this->commercial_name,
            'commercial_registration' => $this->commercial_registration,
            'address' => $this->address,
            'phone' => $this->phone,
            'license_number' => $this->license_number,
            'license_image' => $this->license_image ? asset('storage/'.$this->license_image) : null,
            'status' => $this->status,
            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner->id,
                'f_name' => $this->owner->f_name,
                'l_name' => $this->owner->l_name,
                'email' => $this->owner->email,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
