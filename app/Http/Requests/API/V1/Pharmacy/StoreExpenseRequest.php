<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'category' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'string', 'in:cash,card,bank_transfer,apps'],
            'expense_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'image', 'max:2048'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'title' => ['description' => 'Expense description (e.g. rent, utilities, supplies)'],
            'amount' => ['description' => 'Expense amount'],
            'category' => ['description' => 'Expense category (default: general)'],
            'payment_method' => ['description' => 'Payment method: cash, card, bank_transfer, or apps (default: cash)'],
            'expense_date' => ['description' => 'Date of the expense (Y-m-d)'],
            'notes' => ['description' => 'Optional notes for the expense'],
            'attachment' => ['description' => 'Receipt/invoice image file (max 2MB)'],
        ];
    }
}
