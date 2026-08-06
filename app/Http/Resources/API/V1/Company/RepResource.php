<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Company;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RepResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'user' => [
                'id' => $this->user?->id,
                'f_name' => $this->user?->f_name,
                'l_name' => $this->user?->l_name,
                'email' => $this->user?->email,
                'phone_number' => $this->user?->phone_number,
                'age' => $this->user?->age,
                'gender' => $this->user?->gender,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
