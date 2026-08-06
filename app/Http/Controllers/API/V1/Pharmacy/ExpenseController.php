<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Pharmacy\StoreExpenseRequest;
use App\Http\Requests\API\V1\Pharmacy\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\Pharmacy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Pharmacy Expenses Management.
 *
 * CRUD operations for tracking pharmacy operational expenses
 * such as rent, utilities, supplies, and other costs.
 */
class ExpenseController extends Controller
{
    /**
     * List all expenses for the pharmacy.
     */
    public function index(Request $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        $expenses = Expense::where('pharmacy_id', $pharmacy->id)
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->when($request->filled('payment_method'), fn ($q) => $q->where('payment_method', $request->payment_method))
            ->when($request->filled('date_from'), fn ($q) => $q->where('expense_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->where('expense_date', '<=', $request->date_to))
            ->orderBy('expense_date', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json([
            'data' => $expenses->items()->map(fn (Expense $expense) => $this->formatExpense($expense)),
            'meta' => [
                'current_page' => $expenses->currentPage(),
                'last_page' => $expenses->lastPage(),
                'per_page' => $expenses->perPage(),
                'total' => $expenses->total(),
            ],
        ]);
    }

    /**
     * Create a new expense record.
     */
    public function store(StoreExpenseRequest $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        $validated = $request->validated();
        $validated['pharmacy_id'] = $pharmacy->id;

        if ($request->hasFile('attachment')) {
            $validated['attachment_path'] = $request->file('attachment')->store('expenses', 'public');
        }
        unset($validated['attachment']);

        $expense = Expense::create($validated);

        return response()->json([
            'message' => 'Expense recorded successfully.',
            'data' => $this->formatExpense($expense),
        ], 201);
    }

    /**
     * Show a single expense record.
     */
    public function show(Pharmacy $pharmacy, Expense $expense): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        if ($expense->pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Expense not found for this pharmacy.'], 404);
        }

        return response()->json([
            'data' => $this->formatExpense($expense),
        ]);
    }

    /**
     * Update an expense record.
     */
    public function update(UpdateExpenseRequest $request, Pharmacy $pharmacy, Expense $expense): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        if ($expense->pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Expense not found for this pharmacy.'], 404);
        }

        $validated = $request->validated();

        if ($request->hasFile('attachment')) {
            if ($expense->attachment_path) {
                Storage::disk('public')->delete($expense->attachment_path);
            }
            $validated['attachment_path'] = $request->file('attachment')->store('expenses', 'public');
        }
        unset($validated['attachment']);

        $expense->update($validated);

        return response()->json([
            'message' => 'Expense updated successfully.',
            'data' => $this->formatExpense($expense),
        ]);
    }

    /**
     * Delete an expense record.
     */
    public function destroy(Pharmacy $pharmacy, Expense $expense): JsonResponse
    {
        $this->authorize('manage', $pharmacy);

        if ($expense->pharmacy_id !== $pharmacy->id) {
            return response()->json(['message' => 'Expense not found for this pharmacy.'], 404);
        }

        if ($expense->attachment_path) {
            Storage::disk('public')->delete($expense->attachment_path);
        }

        $expense->delete();

        return response()->json([
            'message' => 'Expense deleted successfully.',
        ]);
    }

    private function formatExpense(Expense $expense): array
    {
        return [
            'id' => $expense->id,
            'pharmacy_id' => $expense->pharmacy_id,
            'title' => $expense->title,
            'amount' => $expense->amount,
            'category' => $expense->category,
            'payment_method' => $expense->payment_method,
            'expense_date' => $expense->expense_date,
            'notes' => $expense->notes,
            'attachment_path' => $expense->attachment_path ? asset('storage/'.$expense->attachment_path) : null,
            'created_at' => $expense->created_at,
            'updated_at' => $expense->updated_at,
        ];
    }
}
