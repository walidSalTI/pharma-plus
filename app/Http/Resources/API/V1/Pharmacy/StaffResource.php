<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Pharmacy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $pivot = $this->pivot ?? $this->staffPharmacies?->first()?->pivot;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->user?->f_name.' '.$this->user?->l_name,
            'email' => $this->user?->email,
            'phone_number' => $this->user?->phone_number,
            $this->mergeWhen($pivot !== null, [
                'permissions' => [
                    'pharmacy_manage' => (bool) ($pivot->pharmacy_manage ?? false),
                    'inventory_manage' => (bool) ($pivot->inventory_manage ?? false),
                    'operating_hours_manage' => (bool) ($pivot->operating_hours_manage ?? false),
                    'orders_process' => (bool) ($pivot->orders_process ?? false),
                    'orders_view_own' => (bool) ($pivot->orders_view_own ?? false),
                ],
                'salary' => $pivot->salary ?? 0,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
