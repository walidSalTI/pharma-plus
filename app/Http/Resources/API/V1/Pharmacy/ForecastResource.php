<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Pharmacy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ForecastResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id ?? null,
            'disease' => $this->disease ?? null,
            'confidence_score' => $this->confidence_score ?? null,
            'related_drugs' => $this->related_drugs ?? [],
            'stocking_recommendations' => $this->recommendations ?? [],
            'scope' => $this->scope ?? 'postal_sector',
            'forecasted_at' => $this->forecasted_at ?? now(),
        ];
    }
}
