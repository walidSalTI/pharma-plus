<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Pharmacy\DemandReportRequest;
use App\Http\Requests\API\V1\Pharmacy\ExpiryReportRequest;
use App\Http\Requests\API\V1\Pharmacy\FinancialReportRequest;
use App\Http\Requests\API\V1\Pharmacy\SlowMovingReportRequest;
use App\Http\Requests\API\V1\Pharmacy\StaffPerformanceReportRequest;
use App\Http\Requests\API\V1\Pharmacy\TopMedicationsReportRequest;
use App\Http\Resources\API\V1\Pharmacy\DemandResource;
use App\Http\Resources\API\V1\Pharmacy\ExpiringInventoryResource;
use App\Http\Resources\API\V1\Pharmacy\FinancialReportResource;
use App\Http\Resources\API\V1\Pharmacy\SlowMovingResource;
use App\Http\Resources\API\V1\Pharmacy\StaffPerformanceResource;
use App\Http\Resources\API\V1\Pharmacy\TopMedicationResource;
use App\Models\Pharmacy;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reporting & Operational Analytics (FR-PH-8).
 *
 * Generates financial summaries, top profitable medications, regional
 * demand, expiry alerts, slow-moving stock and staff performance reports.
 * All calculations are delegated to the ReportService and executed at the
 * database level using aggregations and subqueries.
 */
class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService,
    ) {}

    /**
     * Generate financial summary report (FR-PH-8.1).
     *
     * Returns gross sales, net revenue, COGS, gross profit, returns,
     * operational losses (damages, expenses, salaries), expense breakdown,
     * expired-on-hand loss and net profit for a given date range.
     */
    public function financialSummary(FinancialReportRequest $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        $validated = $request->validated();
        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->endOfDay();

        $report = $this->reportService->financialSummary(
            $pharmacy->id,
            $startDate,
            $endDate,
        );

        return response()->json([
            'status' => 'success',
            'data' => new FinancialReportResource($report),
        ]);
    }

    /**
     * Top most profitable medications.
     *
     * Aggregates completed sale order items per medication and returns the
     * top N items by net profit (unit price minus cost times quantity)
     * within the requested date range. Accepts an optional `limit` of 5 or 10.
     */
    public function topMedications(TopMedicationsReportRequest $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        $validated = $request->validated();
        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->endOfDay();
        $limit = (int) ($validated['limit'] ?? 10);

        $items = $this->reportService->topProfitableMedications(
            $pharmacy->id,
            $startDate,
            $endDate,
            $limit,
        );

        return response()->json([
            'status' => 'success',
            'data' => TopMedicationResource::collection($items),
        ]);
    }

    /**
     * Most demanded medications by geographic region.
     *
     * Aggregates anonymized search telemetry within a Haversine radius of
     * the pharmacy. Group by product, ingredient, or region (grid bucket).
     */
    public function demand(DemandReportRequest $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        $validated = $request->validated();
        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->endOfDay();
        $radius = (float) ($validated['radius'] ?? 10);
        $groupBy = (string) ($validated['group_by'] ?? 'product');
        $limit = (int) ($validated['limit'] ?? 10);

        $items = $this->reportService->demandByRegion(
            $pharmacy->id,
            $startDate,
            $endDate,
            $radius,
            $groupBy,
            $limit,
        );

        return response()->json([
            'status' => 'success',
            'data' => DemandResource::collection($items),
        ]);
    }

    /**
     * Expired & nearing-expiry inventory.
     *
     * Returns expired batches (expiration_date before today) with their loss
     * value, plus batches expiring within an alert window (`days`, default
     * 30) with their stock value.
     */
    public function expiringInventory(ExpiryReportRequest $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        $validated = $request->validated();
        $days = (int) ($validated['days'] ?? 30);

        $report = $this->reportService->expiringInventory(
            $pharmacy->id,
            Carbon::today(),
            $days,
        );

        return response()->json([
            'status' => 'success',
            'data' => new ExpiringInventoryResource($report),
        ]);
    }

    /**
     * Stagnant / slow-moving stock.
     *
     * Inventory items with stock > 0 that were never sold or have no
     * completed sale within the analysis window. Window defaults to the
     * last `days` (90) days ending today, or start_date/end_date when given.
     */
    public function slowMoving(SlowMovingReportRequest $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        $validated = $request->validated();
        $days = (int) ($validated['days'] ?? 90);
        $endDate = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : Carbon::now()->endOfDay();
        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : $endDate->copy()->subDays($days)->startOfDay();

        $items = $this->reportService->slowMovingStock(
            $pharmacy->id,
            $startDate,
            $endDate,
        );

        return response()->json([
            'status' => 'success',
            'data' => SlowMovingResource::collection($items),
        ]);
    }

    /**
     * Staff performance analytics.
     *
     * Groups completed orders by the processing staff member
     * (medication_orders.pharmacist_id) and reports total orders handled,
     * total sales volume, average order value, returns and return rate.
     */
    public function staffPerformance(StaffPerformanceReportRequest $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        $validated = $request->validated();
        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->endOfDay();

        $items = $this->reportService->staffPerformance(
            $pharmacy->id,
            $startDate,
            $endDate,
        );

        return response()->json([
            'status' => 'success',
            'data' => StaffPerformanceResource::collection($items),
        ]);
    }

    /**
     * Export inventory to file (FR-PH-8.2).
     *
     * One-click extraction of the live pharmacy asset registry,
     * stock levels, and valuation tiers into a structured file format.
     * Requires `inventory_manage` permission.
     *
     * @todo Implement Excel/PDF export
     */
    public function export(Request $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manageInventory', $pharmacy);

        return response()->json([
            'message' => 'Export feature is not yet implemented.',
        ]);
    }
}
