<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Expense;
use App\Models\MedicationOrder;
use App\Models\Salary;
use Carbon\Carbon;

class FinancialReportService
{
    public function generateReport(int $pharmacyId, Carbon $startDate, Carbon $endDate): array
    {
        $salesData = MedicationOrder::where('pharmacy_id', $pharmacyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->selectRaw("
                SUM(CASE WHEN type = 'sale' THEN total_price ELSE 0 END) as tp_sale,
                SUM(CASE WHEN type = 'sale' THEN total_cost ELSE 0 END) as tc_sale,
                SUM(CASE WHEN type = 'customer_return' THEN total_price ELSE 0 END) as tp_return,
                SUM(CASE WHEN type = 'customer_return' THEN total_cost ELSE 0 END) as tc_return,
                SUM(CASE WHEN type = 'damaged' THEN total_cost ELSE 0 END) as tc_damaged
            ")
            ->first();

        $tpSale = (float) ($salesData->tp_sale ?? 0);
        $tcSale = (float) ($salesData->tc_sale ?? 0);
        $tpReturn = (float) ($salesData->tp_return ?? 0);
        $tcReturn = (float) ($salesData->tc_return ?? 0);
        $tcDamaged = (float) ($salesData->tc_damaged ?? 0);

        $netRevenue = $tpSale - $tpReturn;
        $netCogs = $tcSale - $tcReturn;
        $grossProfit = $netRevenue - $netCogs;

        $expenses = Expense::where('pharmacy_id', $pharmacyId)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->sum('amount');

        $salaries = Salary::where('pharmacy_id', $pharmacyId)
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('amount');

        $netProfit = $grossProfit - $tcDamaged - $expenses - $salaries;

        return [
            'gross_sales' => $tpSale,
            'returns_amount' => $tpReturn,
            'net_revenue' => $netRevenue,
            'gross_cogs' => $tcSale,
            'returns_cogs' => $tcReturn,
            'net_cogs' => $netCogs,
            'gross_profit' => $grossProfit,
            'operational_losses' => [
                'damaged_cost' => $tcDamaged,
                'expenses' => $expenses,
                'salaries' => $salaries,
            ],
            'net_profit' => $netProfit,
        ];
    }
}
