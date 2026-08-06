<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy;
use App\Services\FinancialReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reporting & Operational Analytics (FR-PH-8).
 *
 * Generates financial summary reports and provides one-click
 * inventory export functionality.
 */
class ReportController extends Controller
{
    public function __construct(
        protected FinancialReportService $reportService,
    ) {}

    /**
     * Generate financial summary report (FR-PH-8.1).
     *
     * Returns gross sales, net revenue, COGS, gross profit,
     * operational losses (damages, expenses, salaries), and net profit
     * for a given date range.
     */
    public function financialSummary(Request $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->endOfDay();

        $report = $this->reportService->generateReport(
            $pharmacy->id,
            $startDate,
            $endDate,
        );

        return response()->json([
            'status' => 'success',
            'data' => $report,
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
