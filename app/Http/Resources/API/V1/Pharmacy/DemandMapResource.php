<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Pharmacy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DemandMapResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'hotspots' => $this->hotspots ?? [],
            'top_molecules' => $this->top_molecules ?? [],
            'market_direction' => $this->market_direction ?? [],
            'generated_at' => now(),
        ];
    }
}
