<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Pharmacy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'period' => $this->period ?? null,
            'top_selling_drugs' => $this->top_selling_drugs ?? [],
            'market_shifts' => $this->market_shifts ?? [],
            'disease_indicators' => $this->disease_indicators ?? [],
            'generated_at' => now(),
        ];
    }
}
