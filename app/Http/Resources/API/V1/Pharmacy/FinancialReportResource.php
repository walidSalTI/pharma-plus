<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Pharmacy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'gross_sales' => $this['gross_sales'],
            'returns_amount' => $this['returns_amount'],
            'returns_count' => $this['returns_count'],
            'net_revenue' => $this['net_revenue'],
            'gross_cogs' => $this['gross_cogs'],
            'returns_cogs' => $this['returns_cogs'],
            'net_cogs' => $this['net_cogs'],
            'gross_profit' => $this['gross_profit'],
            'operational_losses' => $this['operational_losses'],
            'expense_breakdown' => $this['expense_breakdown'],
            'expired_inventory_loss' => $this['expired_inventory_loss'],
            'net_profit' => $this['net_profit'],
        ];
    }
}
