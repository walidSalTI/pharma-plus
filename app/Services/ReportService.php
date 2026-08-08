<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActiveIngredient;
use App\Models\Expense;
use App\Models\Medication;
use App\Models\MedicationOrder;
use App\Models\Pharmacist;
use App\Models\Pharmacy;
use App\Models\PharmacyInventoryBatch;
use App\Models\Salary;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Pharmacy reporting & operational analytics (FR-PH-8).
 *
 * All calculations are executed at the database level using raw SQL
 * aggregations (SUM, COUNT, AVG), conditional CASE expressions and
 * correlated/lateral subqueries to keep the workload on the DB engine.
 */
class ReportService
{
    /**
     * Financial summary over a date range.
     *
     * Aggregates completed orders grouped by type (sale, customer_return,
     * damaged), sums expenses (grouped by category) and salaries
     * (net_amount), and appends the current expired-on-hand inventory loss
     * value. Net profit = gross_profit - damaged_loss - expenses - salaries.
     */
    public function financialSummary(string $pharmacyId, Carbon $startDate, Carbon $endDate): array
    {
        $orders = MedicationOrder::query()
            ->where('pharmacy_id', $pharmacyId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("
                SUM(CASE WHEN type = 'sale' THEN total_price ELSE 0 END) as gross_sales,
                SUM(CASE WHEN type = 'sale' THEN total_cost ELSE 0 END) as gross_cogs,
                SUM(CASE WHEN type = 'customer_return' THEN total_price ELSE 0 END) as returns_amount,
                SUM(CASE WHEN type = 'customer_return' THEN total_cost ELSE 0 END) as returns_cogs,
                SUM(CASE WHEN type = 'customer_return' THEN 1 ELSE 0 END) as returns_count,
                SUM(CASE WHEN type = 'damaged' THEN total_cost ELSE 0 END) as damaged_loss
            ")
            ->first();

        $grossSales = (float) ($orders->gross_sales ?? 0);
        $grossCogs = (float) ($orders->gross_cogs ?? 0);
        $returnsAmount = (float) ($orders->returns_amount ?? 0);
        $returnsCogs = (float) ($orders->returns_cogs ?? 0);
        $returnsCount = (int) ($orders->returns_count ?? 0);
        $damagedLoss = (float) ($orders->damaged_loss ?? 0);

        $netRevenue = $grossSales - $returnsAmount;
        $netCogs = $grossCogs - $returnsCogs;
        $grossProfit = $netRevenue - $netCogs;

        $expenseBreakdown = Expense::query()
            ->where('pharmacy_id', $pharmacyId)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->pluck('total', 'category');

        $expensesTotal = (float) $expenseBreakdown->sum();

        $salariesTotal = (float) Salary::query()
            ->where('pharmacy_id', $pharmacyId)
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('net_amount');

        $expiredLoss = $this->expiredOnHandLoss($pharmacyId);

        $netProfit = $grossProfit - $damagedLoss - $expensesTotal - $salariesTotal;

        return [
            'gross_sales' => round($grossSales, 2),
            'returns_amount' => round($returnsAmount, 2),
            'returns_count' => $returnsCount,
            'net_revenue' => round($netRevenue, 2),
            'gross_cogs' => round($grossCogs, 2),
            'returns_cogs' => round($returnsCogs, 2),
            'net_cogs' => round($netCogs, 2),
            'gross_profit' => round($grossProfit, 2),
            'operational_losses' => [
                'damaged_cost' => round($damagedLoss, 2),
                'expenses' => round($expensesTotal, 2),
                'salaries' => round($salariesTotal, 2),
            ],
            'expense_breakdown' => $expenseBreakdown->map(fn ($total, $category) => [
                'category' => $category,
                'total' => round((float) $total, 2),
            ])->values()->all(),
            'expired_inventory_loss' => [
                'value' => round($expiredLoss, 2),
                'note' => 'Value of on-hand stock (qty * wholesale_price) with expiration_date in the past. Reported separately; not deducted from net profit.',
            ],
            'net_profit' => round($netProfit, 2),
        ];
    }

    /**
     * Top most profitable medications.
     *
     * Aggregates completed sale order items per medication and computes
     * net profit as SUM((unit_price - unit_cost) * quantity). The unit cost
     * falls back from the item wholesale snapshot to the batch wholesale
     * price when the snapshot is missing.
     */
    public function topProfitableMedications(string $pharmacyId, Carbon $startDate, Carbon $endDate, int $limit = 10): Collection
    {
        $rows = DB::table('medication_order_items as oi')
            ->join('medication_orders as mo', 'mo.id', '=', 'oi.medication_order_id')
            ->leftJoin('pharmacy_inventory_batches as b', 'b.id', '=', 'oi.batch_id')
            ->where('mo.pharmacy_id', $pharmacyId)
            ->where('mo.status', 'completed')
            ->where('mo.type', 'sale')
            ->whereBetween('mo.created_at', [$startDate, $endDate])
            ->selectRaw('
                oi.medication_id,
                SUM(oi.quantity) as units_sold,
                SUM(oi.price * oi.quantity) as revenue,
                SUM(COALESCE(oi.wholesale_price_at_sale, b.wholesale_price, 0) * oi.quantity) as cost,
                SUM((oi.price - COALESCE(oi.wholesale_price_at_sale, b.wholesale_price, 0)) * oi.quantity) as net_profit
            ')
            ->groupBy('oi.medication_id')
            ->orderByDesc('net_profit')
            ->limit($limit)
            ->get();

        $medications = $this->medicationsKeyedById($rows->pluck('medication_id'));

        return $rows->map(function ($row) use ($medications) {
            $medication = $medications->get($row->medication_id);

            return (object) [
                'medication_id' => $row->medication_id,
                'name' => $medication?->product?->name,
                'units_sold' => (int) $row->units_sold,
                'revenue' => round((float) $row->revenue, 2),
                'cost' => round((float) $row->cost, 2),
                'net_profit' => round((float) $row->net_profit, 2),
            ];
        })->values();
    }

    /**
     * Most demanded medications by geographic region.
     *
     * Aggregates anonymized patient search telemetry within a geographic
     * bounding box around the pharmacy (radius expressed as a lat/lng
     * delta). Grouping is by resolved product name (falling back to the
     * raw query), resolved active ingredient, or by a rounded lat/lng grid
     * bucket for regional breakdowns. Filtering and aggregation happen at
     * the database level.
     */
    public function demandByRegion(
        string $pharmacyId,
        Carbon $startDate,
        Carbon $endDate,
        float $radiusKm = 10.0,
        string $groupBy = 'product',
        int $limit = 10,
    ): Collection {
        $pharmacy = Pharmacy::findOrFail($pharmacyId);
        $latitude = (float) $pharmacy->latitude;
        $longitude = (float) $pharmacy->longitude;

        $latDelta = $radiusKm / 111.0;
        $lngDelta = $radiusKm / (111.0 * max(cos(deg2rad($latitude)), 0.01));

        $query = DB::table('search_telemetries as t')
            ->whereBetween('t.created_at', [$startDate, $endDate])
            ->whereBetween('t.latitude', [$latitude - $latDelta, $latitude + $latDelta])
            ->whereBetween('t.longitude', [$longitude - $lngDelta, $longitude + $lngDelta]);

        if ($groupBy === 'ingredient') {
            return $this->demandGroupedByIngredient($query, $limit);
        }

        if ($groupBy === 'region') {
            return $query
                ->selectRaw("
                    COALESCE(NULLIF(t.resolved_product_name, ''), t.searched_query) as medication,
                    ROUND(t.latitude, 2) as lat_bucket,
                    ROUND(t.longitude, 2) as lng_bucket,
                    COUNT(*) as demand_count
                ")
                ->groupBy('medication', 'lat_bucket', 'lng_bucket')
                ->orderByDesc('demand_count')
                ->limit($limit)
                ->get()
                ->map(fn ($row) => (object) [
                    'medication' => $row->medication,
                    'region' => [
                        'latitude' => (float) $row->lat_bucket,
                        'longitude' => (float) $row->lng_bucket,
                    ],
                    'demand_count' => (int) $row->demand_count,
                ]);
        }

        return $query
            ->selectRaw("
                COALESCE(NULLIF(t.resolved_product_name, ''), t.searched_query) as medication,
                COUNT(*) as demand_count
            ")
            ->groupBy('medication')
            ->orderByDesc('demand_count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => (object) [
                'medication' => $row->medication,
                'demand_count' => (int) $row->demand_count,
            ]);
    }

    /**
     * Expired & nearing-expiry inventory.
     *
     * Expired: batches with quantity > 0 and expiration_date before the
     * cutoff. Nearing expiry: batches expiring within the alert window.
     * Both are grouped per inventory item with units and monetary value.
     */
    public function expiringInventory(string $pharmacyId, ?Carbon $cutoff = null, int $alertDays = 30): array
    {
        $today = $cutoff ?? Carbon::today();
        $alertDate = $today->copy()->addDays($alertDays);

        $expiredRows = $this->batchRowsForPharmacy($pharmacyId)
            ->whereDate('b.expiration_date', '<', $today)
            ->selectRaw('
                inv.id as inventory_id,
                inv.medication_id,
                b.expiration_date,
                SUM(b.quantity) as quantity,
                SUM(b.quantity * b.wholesale_price) as value
            ')
            ->groupBy('inv.id', 'inv.medication_id', 'b.expiration_date')
            ->orderBy('b.expiration_date')
            ->get();

        $nearingRows = $this->batchRowsForPharmacy($pharmacyId)
            ->whereDate('b.expiration_date', '>=', $today)
            ->whereDate('b.expiration_date', '<=', $alertDate)
            ->selectRaw('
                inv.id as inventory_id,
                inv.medication_id,
                b.expiration_date,
                SUM(b.quantity) as quantity,
                SUM(b.quantity * b.wholesale_price) as value
            ')
            ->groupBy('inv.id', 'inv.medication_id', 'b.expiration_date')
            ->orderBy('b.expiration_date')
            ->get();

        $medications = $this->medicationsKeyedById(
            $expiredRows->pluck('medication_id')->merge($nearingRows->pluck('medication_id')),
        );

        return [
            'as_of' => $today->toDateString(),
            'expired' => [
                'total_units' => (int) $expiredRows->sum('quantity'),
                'total_loss_value' => round((float) $expiredRows->sum('value'), 2),
                'items' => $expiredRows
                    ->map(fn ($row) => $this->expiryRowToArray($row, $medications))
                    ->values()
                    ->all(),
            ],
            'nearing_expiry' => [
                'days_window' => $alertDays,
                'total_units' => (int) $nearingRows->sum('quantity'),
                'total_stock_value' => round((float) $nearingRows->sum('value'), 2),
                'items' => $nearingRows
                    ->map(fn ($row) => $this->expiryRowToArray($row, $medications))
                    ->values()
                    ->all(),
            ],
        ];
    }

    /**
     * Stagnant / slow-moving stock.
     *
     * Inventory items with stock > 0 that have never been sold or have no
     * completed sale within the analysis window (before windowStart).
     * Uses a correlated MAX(created_at) subquery per medication.
     */
    public function slowMovingStock(string $pharmacyId, Carbon $windowStart, Carbon $windowEnd): Collection
    {
        $lastSalesSubquery = DB::table('medication_order_items as oi')
            ->join('medication_orders as mo', 'mo.id', '=', 'oi.medication_order_id')
            ->where('mo.pharmacy_id', $pharmacyId)
            ->where('mo.type', 'sale')
            ->where('mo.status', 'completed')
            ->where('mo.created_at', '<=', $windowEnd)
            ->selectRaw('oi.medication_id, MAX(mo.created_at) as last_sold_at')
            ->groupBy('oi.medication_id');

        $rows = DB::table('pharmacy_inventories as inv')
            ->leftJoinSub($lastSalesSubquery, 'sales', 'sales.medication_id', '=', 'inv.medication_id')
            ->where('inv.pharmacy_id', $pharmacyId)
            ->where('inv.stock', '>', 0)
            ->where(function ($query) use ($windowStart) {
                $query->whereNull('sales.last_sold_at')
                    ->orWhere('sales.last_sold_at', '<', $windowStart);
            })
            ->selectRaw('
                inv.id,
                inv.medication_id,
                inv.stock,
                inv.price,
                inv.stock * inv.price as stock_value,
                sales.last_sold_at
            ')
            ->orderByRaw('sales.last_sold_at IS NULL DESC')
            ->orderBy('sales.last_sold_at')
            ->get();

        $medications = $this->medicationsKeyedById($rows->pluck('medication_id'));

        return $rows->map(function ($row) use ($medications, $windowEnd) {
            $lastSoldAt = $row->last_sold_at ? Carbon::parse($row->last_sold_at) : null;
            $medication = $medications->get($row->medication_id);

            return (object) [
                'inventory_id' => $row->id,
                'medication_id' => $row->medication_id,
                'name' => $medication?->product?->name,
                'stock' => (int) $row->stock,
                'price' => round((float) $row->price, 2),
                'stock_value' => round((float) $row->stock_value, 2),
                'last_sold_at' => $lastSoldAt?->toDateTimeString(),
                'days_since_last_sale' => $lastSoldAt instanceof Carbon ? (int) $lastSoldAt->diffInDays($windowEnd) : null,
                'never_sold' => ! $lastSoldAt instanceof Carbon,
            ];
        })->values();
    }

    /**
     * Staff performance analytics.
     *
     * Groups completed orders by the processing staff member
     * (medication_orders.pharmacist_id) and reports total orders handled,
     * total sales volume, average order value, return counts and the
     * return rate (returns / sales orders).
     */
    public function staffPerformance(string $pharmacyId, Carbon $startDate, Carbon $endDate): Collection
    {
        $rows = MedicationOrder::query()
            ->where('pharmacy_id', $pharmacyId)
            ->where('status', 'completed')
            ->whereIn('type', ['sale', 'customer_return'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("
                pharmacist_id,
                SUM(CASE WHEN type = 'sale' THEN 1 ELSE 0 END) as total_orders,
                SUM(CASE WHEN type = 'sale' THEN total_price ELSE 0 END) as total_sales_volume,
                SUM(CASE WHEN type = 'sale' THEN total_price ELSE 0 END)
                    / NULLIF(SUM(CASE WHEN type = 'sale' THEN 1 ELSE 0 END), 0) as avg_order_value,
                SUM(CASE WHEN type = 'customer_return' THEN 1 ELSE 0 END) as total_returns,
                SUM(CASE WHEN type = 'customer_return' THEN total_price ELSE 0 END) as returns_amount
            ")
            ->groupBy('pharmacist_id')
            ->get();

        $pharmacists = Pharmacist::with('user')
            ->whereIn('id', $rows->pluck('pharmacist_id')->filter())
            ->get()
            ->keyBy('id');

        return $rows->map(function ($row) use ($pharmacists) {
            $staff = $pharmacists->get($row->pharmacist_id);
            $totalOrders = (int) $row->total_orders;
            $totalReturns = (int) $row->total_returns;

            return (object) [
                'pharmacist_id' => $row->pharmacist_id,
                'name' => $staff
                    ? trim(($staff->user?->f_name ?? '').' '.($staff->user?->l_name ?? ''))
                    : 'Unassigned',
                'total_orders' => $totalOrders,
                'total_sales_volume' => round((float) $row->total_sales_volume, 2),
                'avg_order_value' => round((float) $row->avg_order_value, 2),
                'total_returns' => $totalReturns,
                'returns_amount' => round((float) $row->returns_amount, 2),
                'return_rate' => $totalOrders > 0 ? round($totalReturns / $totalOrders, 4) : 0,
            ];
        })->sortByDesc('total_sales_volume')->values();
    }

    /**
     * Current value of expired on-hand stock (quantity * wholesale price).
     */
    protected function expiredOnHandLoss(string $pharmacyId): float
    {
        return (float) PharmacyInventoryBatch::query()
            ->join('pharmacy_inventories', 'pharmacy_inventories.id', '=', 'pharmacy_inventory_batches.pharmacy_inventory_id')
            ->where('pharmacy_inventories.pharmacy_id', $pharmacyId)
            ->where('pharmacy_inventory_batches.quantity', '>', 0)
            ->whereDate('pharmacy_inventory_batches.expiration_date', '<', Carbon::today())
            ->sum(DB::raw('pharmacy_inventory_batches.quantity * pharmacy_inventory_batches.wholesale_price'));
    }

    /**
     * Base query for inventory batches joined to their inventory rows.
     */
    protected function batchRowsForPharmacy(string $pharmacyId): Builder
    {
        return DB::table('pharmacy_inventory_batches as b')
            ->join('pharmacy_inventories as inv', 'inv.id', '=', 'b.pharmacy_inventory_id')
            ->where('inv.pharmacy_id', $pharmacyId)
            ->where('b.quantity', '>', 0);
    }

    protected function expiryRowToArray($row, Collection $medications): array
    {
        $medication = $medications->get($row->medication_id);

        return [
            'inventory_id' => $row->inventory_id,
            'medication_id' => $row->medication_id,
            'name' => $medication?->product?->name,
            'expiration_date' => $row->expiration_date,
            'quantity' => (int) $row->quantity,
            'value' => round((float) $row->value, 2),
        ];
    }

    protected function demandGroupedByIngredient($query, int $limit): Collection
    {
        $rows = $query
            ->selectRaw('
                COALESCE(t.resolved_active_ingredient_id, t.searched_query) as medication_key,
                COUNT(*) as demand_count
            ')
            ->groupBy('medication_key')
            ->orderByDesc('demand_count')
            ->limit($limit)
            ->get();

        $ingredients = ActiveIngredient::query()
            ->whereIn('id', $rows->pluck('medication_key')->filter(fn ($key) => Str::isUuid((string) $key)))
            ->get()
            ->keyBy('id');

        return $rows->map(function ($row) use ($ingredients) {
            $key = (string) $row->medication_key;
            $name = Str::isUuid($key)
                ? ($ingredients->get($key)?->ingredient_name_en ?? 'Unknown Ingredient')
                : $key;

            return (object) [
                'medication' => $name,
                'demand_count' => (int) $row->demand_count,
            ];
        });
    }

    protected function medicationsKeyedById(Collection $ids): Collection
    {
        $ids = $ids->filter()->unique();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Medication::query()
            ->with('product')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
    }
}
