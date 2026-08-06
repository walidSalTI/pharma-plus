<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Pharmacy\DemandMapResource;
use App\Models\Pharmacy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Regional Demand Tracking (FR-PH-3).
 *
 * Provides an interactive map displaying the most sought-after medications
 * and active ingredients within the pharmacy's region, built from localized
 * anonymized patient search queries and GPS telemetry.
 *
 * @todo Connect to real aggregated search-query analytics pipeline
 */
class DemandController extends Controller
{
    /**
     * Get regional demand heatmap data (FR-PH-3.1, FR-PH-3.2).
     *
     * Returns demand hotspots, top molecules, and market direction vectors
     * filtered by active ingredient, timeframe, and geospatial radius.
     * Accessible by owner or staff with `pharmacy_manage` permission.
     *
     * Query parameters:
     * - `ingredient` (string|null): Filter by active ingredient name
     * - `timeframe` (string, default '7'): Last 7 or 30 days
     * - `radius` (int|null): Geospatial radius in KM from the pharmacy
     */
    public function demandMap(Request $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        $demandData = (object) [
            'hotspots' => [],
            'top_molecules' => [],
            'market_direction' => [],
        ];

        return response()->json([
            'data' => new DemandMapResource($demandData),
            'meta' => [
                'filters_applied' => [
                    'ingredient' => $request->input('ingredient'),
                    'timeframe' => $request->input('timeframe', '7'),
                    'radius' => $request->input('radius'),
                ],
                'pharmacy_location' => [
                    'latitude' => $pharmacy->latitude,
                    'longitude' => $pharmacy->longitude,
                ],
            ],
        ]);
    }
}
