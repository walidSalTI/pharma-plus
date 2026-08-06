<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Pharmacy\UpsertOperatingHoursRequest;
use App\Http\Resources\API\V1\Pharmacy\OperatingHourResource;
use App\Models\Pharmacy;
use App\Models\PharmacyOperatingHour;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Pharmacy Operating Hours & Vacation Management (FR-PH-6.1, FR-PH-6.2).
 *
 * Manages the weekly operating hours schedule and temporary closures.
 * When a vacation/closure is declared, the pharmacy is hidden from
 * patient-facing search maps during that period.
 */
class OperatingHourController extends Controller
{
    /**
     * Get operating hours (FR-PH-6.1).
     *
     * Returns the 7-day operating hours schedule for the pharmacy.
     * Accessible by owner or staff with `operating_hours_manage` permission.
     */
    public function index(Request $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manageOperatingHours', $pharmacy);

        $hours = $pharmacy->pharmacyOperatingHours()
            ->orderBy('day_of_week')
            ->get();

        return response()->json([
            'data' => OperatingHourResource::collection($hours),
        ]);
    }

    /**
     * Upsert operating hours (FR-PH-6.1).
     *
     * Replaces the full 7-day schedule atomically. Each day specifies
     * opening/closing time, whether the pharmacy is open 24 hours,
     * or whether it is closed on that day.
     * Requires `operating_hours_manage` permission.
     */
    public function upsert(UpsertOperatingHoursRequest $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manageOperatingHours', $pharmacy);

        $validated = $request->validated();

        $pharmacy->pharmacyOperatingHours()->delete();

        $hours = [];
        foreach ($validated['hours'] as $dayData) {
            $hours[] = [
                'id' => (string) Str::uuid(),
                'pharmacy_id' => $pharmacy->id,
                'day_of_week' => $dayData['day_of_week'],
                'opening_time' => $dayData['opening_time'] ?? null,
                'closing_time' => $dayData['closing_time'] ?? null,
                'is_24_hours' => $dayData['is_24_hours'] ?? false,
                'is_closed' => $dayData['is_closed'] ?? false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        PharmacyOperatingHour::insert($hours);

        $updatedHours = $pharmacy->pharmacyOperatingHours()
            ->orderBy('day_of_week')
            ->get();

        return response()->json([
            'message' => 'Operating hours updated successfully.',
            'data' => OperatingHourResource::collection($updatedHours),
        ]);
    }

    /**
     * Declare a temporary vacation or closure (FR-PH-6.2).
     *
     * Sets `is_closed = true` for the specified day range. While
     * declared closed, the pharmacy is hidden from patient-facing
     * search results and order placement.
     * Requires `operating_hours_manage` permission.
     *
     * Body parameters:
     * - `start_day` (int, required): Day index to start closure (0-6)
     * - `end_day` (int, required): Day index to end closure (0-6)
     */
    public function declareVacation(Request $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manageOperatingHours', $pharmacy);

        $validated = $request->validate([
            'start_day' => ['required', 'integer', 'between:0,6'],
            'end_day' => ['required', 'integer', 'between:0,6'],
            'is_closed' => ['nullable', 'boolean'],
        ]);

        $isClosed = $validated['is_closed'] ?? true;

        $days = range($validated['start_day'], $validated['end_day']);

        foreach ($days as $day) {
            PharmacyOperatingHour::updateOrCreate(
                [
                    'pharmacy_id' => $pharmacy->id,
                    'day_of_week' => $day,
                ],
                [
                    'is_closed' => $isClosed,
                    'is_24_hours' => false,
                    'updated_at' => now(),
                ]
            );
        }

        $updatedHours = $pharmacy->pharmacyOperatingHours()
            ->orderBy('day_of_week')
            ->get();

        $status = $isClosed ? 'closed for vacation' : 'reopened';

        return response()->json([
            'message' => "Pharmacy has been {$status} from day {$validated['start_day']} to {$validated['end_day']}.",
            'data' => OperatingHourResource::collection($updatedHours),
        ]);
    }
}
