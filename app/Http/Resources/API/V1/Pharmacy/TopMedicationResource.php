<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Pharmacy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TopMedicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'medication_id' => $this->medication_id,
            'name' => $this->name,
            'units_sold' => $this->units_sold,
            'revenue' => $this->revenue,
            'cost' => $this->cost,
            'net_profit' => $this->net_profit,
        ];
    }
}
