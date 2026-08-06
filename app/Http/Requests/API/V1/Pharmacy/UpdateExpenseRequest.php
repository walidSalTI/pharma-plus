<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'category' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'string', 'in:cash,card,bank_transfer,apps'],
            'expense_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'image', 'max:2048'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'title' => ['description' => 'Updated expense description'],
            'amount' => ['description' => 'Updated expense amount'],
            'category' => ['description' => 'Updated expense category'],
            'payment_method' => ['description' => 'Updated payment method: cash, card, bank_transfer, or apps'],
            'expense_date' => ['description' => 'Updated expense date (Y-m-d)'],
            'notes' => ['description' => 'Updated notes for the expense'],
            'attachment' => ['description' => 'Updated receipt/invoice image file (max 2MB)'],
        ];
    }
}
