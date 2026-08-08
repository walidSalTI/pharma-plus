<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Pharmacy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpiringInventoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'as_of' => $this['as_of'],
            'expired' => $this['expired'],
            'nearing_expiry' => $this['nearing_expiry'],
        ];
    }
}
