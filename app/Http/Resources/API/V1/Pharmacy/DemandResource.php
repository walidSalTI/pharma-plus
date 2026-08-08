<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Pharmacy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DemandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'medication' => $this->medication,
            'demand_count' => $this->demand_count,
            'region' => $this->region ?? null,
        ];
    }
}
