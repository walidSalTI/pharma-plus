<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Pharmacy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SlowMovingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'inventory_id' => $this->inventory_id,
            'medication_id' => $this->medication_id,
            'name' => $this->name,
            'stock' => $this->stock,
            'price' => $this->price,
            'stock_value' => $this->stock_value,
            'last_sold_at' => $this->last_sold_at,
            'days_since_last_sale' => $this->days_since_last_sale,
            'never_sold' => $this->never_sold,
        ];
    }
}
