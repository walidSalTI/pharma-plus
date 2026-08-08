<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Pharmacy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffPerformanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'pharmacist_id' => $this->pharmacist_id,
            'name' => $this->name,
            'total_orders' => $this->total_orders,
            'total_sales_volume' => $this->total_sales_volume,
            'avg_order_value' => $this->avg_order_value,
            'total_returns' => $this->total_returns,
            'returns_amount' => $this->returns_amount,
            'return_rate' => $this->return_rate,
        ];
    }
}
