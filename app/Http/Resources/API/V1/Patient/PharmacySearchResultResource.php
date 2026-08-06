<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PharmacySearchResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'pharmacy_id' => $this->pharmacy_id,
            'pharmacy_name' => $this->pharmacy_name,
            'pharmacy_address' => $this->pharmacy_address,
            'pharmacy_latitude' => $this->pharmacy_latitude,
            'pharmacy_longitude' => $this->pharmacy_longitude,
            'distance_km' => round($this->distance_km, 2),
            'suitability_score' => round($this->suitability_score, 2),
            'match_type' => $this->match_type,
            'medication_id' => $this->medication_id,
            'trade_name' => $this->trade_name,
            'price' => (float) $this->price,
            'stock' => (int) $this->stock,
            'average_rating' => $this->average_rating ? round((float) $this->average_rating, 1) : null,
            'conflicts' => $this->conflicts ?? [],
        ];
    }
}
