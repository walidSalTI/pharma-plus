<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Pharmacy\StoreSalaryRequest;
use App\Http\Requests\API\V1\Pharmacy\UpdateSalaryRequest;
use App\Models\Pharmacist;
use App\Models\Pharmacy;
use App\Models\Salary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pharmacy Salaries Management.
 *
 * CRUD operations for tracking pharmacist/staff salary payments
 * made by the pharmacy. Base salary is pulled from the
 * pharmacy_pharmacist pivot; bonus and deductions are manual;
 * net_amount is calculated server-side.
 */
class SalaryController extends Controller
{
    /**
     * List all salary payments for the pharmacy.
     */
    public function index(Request $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        $salaries = Salary::where('pharmacy_id', $pharmacy->id)
            ->with('user')
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('salary_period'), fn ($q) => $q->where('salary_period', $request->salary_period))
            ->when($request->filled('payment_method'), fn ($q) => $q->where('payment_method', $request->payment_method))
            ->when($request->filled('date_from'), fn ($q) => $q->where('paid_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->where('paid_at', '<=', $request->date_to))
            ->orderBy('paid_at', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json([
            'data' => $salaries->items(),
            'meta' => [
                'current_page' => $salaries->currentPage(),
                'last_page' => $salaries->lastPage(),
                'per_page' => $salaries->perPage(),
                'total' => $salaries->total(),
            ],
        ]);
    }

    /**
     * Create a new salary payment record.
     *
     * base_amount is auto-pulled from the pharmacy_pharmacist pivot
     * when user_id is provided; otherwise falls back to manual input.
     * net_amount = base_amount + bonus - deductions.
     */
    public function store(StoreSalaryRequest $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        $validated = $request->validated();
        $validated['pharmacy_id'] = $pharmacy->id;

        if (! empty($validated['user_id']) && ! isset($validated['base_amount'])) {
            $validated['base_amount'] = $this->getPivotSalary($pharmacy->id, $validated['user_id']);
        }

        $validated['net_amount'] = ($validated['base_amount'] ?? 0)
            + ($validated['bonus'] ?? 0)
            - ($validated['deductions'] ?? 0);

        $salary = Salary::create($validated);
        $salary->load('user');

        return response()->json([
            'message' => 'Salary payment recorded successfully.',
            'data' => $salary,
        ], 201);
    }

    /**
     * Show a single salary payment record.
     */
    public function show(Pharmacy $pharmacy, Salary $salary): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        if ($salary->pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Salary record not found for this pharmacy.'], 404);
        }

        $salary->load('user');

        return response()->json([
            'data' => $salary,
        ]);
    }

    /**
     * Update a salary payment record.
     *
     * If user_id changes, re-lookups the pivot salary for the new user.
     * net_amount is always recalculated from base + bonus - deductions.
     */
    public function update(UpdateSalaryRequest $request, Pharmacy $pharmacy, Salary $salary): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        if ($salary->pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Salary record not found for this pharmacy.'], 404);
        }

        $validated = $request->validated();

        if (! empty($validated['user_id']) && ! isset($validated['base_amount'])) {
            $validated['base_amount'] = $this->getPivotSalary($pharmacy->id, $validated['user_id']);
        }

        $base = $validated['base_amount'] ?? $salary->base_amount;
        $bonus = $validated['bonus'] ?? $salary->bonus;
        $deductions = $validated['deductions'] ?? $salary->deductions;
        $validated['net_amount'] = $base + $bonus - $deductions;

        $salary->update($validated);
        $salary->load('user');

        return response()->json([
            'message' => 'Salary record updated successfully.',
            'data' => $salary,
        ]);
    }

    /**
     * Delete a salary payment record.
     */
    public function destroy(Pharmacy $pharmacy, Salary $salary): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        if ($salary->pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Salary record not found for this pharmacy.'], 404);
        }

        $salary->delete();

        return response()->json([
            'message' => 'Salary record deleted successfully.',
        ]);
    }

    /**
     * Look up the salary from the pharmacy_pharmacist pivot table.
     */
    private function getPivotSalary(string $pharmacyId, string $userId): float
    {
        $pharmacist = Pharmacist::where('user_id', $userId)->first();

        if (! $pharmacist) {
            return 0;
        }

        $pivot = Pharmacy::find($pharmacyId)
            ?->staffPharmacists()
            ->where('pharmacist_id', $pharmacist->id)
            ->first()?->pivot;

        return $pivot?->salary ?? 0;
    }
}
