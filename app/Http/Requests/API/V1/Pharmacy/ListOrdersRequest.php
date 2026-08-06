<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class ListOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->pharmacist !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:pending,confirmed,ready,completed,cancelled'],
            'type' => ['nullable', 'string', 'in:sale,purchase,damaged,supplier_return,customer_return,damage_reversal'],
            'source' => ['nullable', 'string', 'in:app,POS'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'is_returned' => ['nullable', 'boolean'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'min_cost' => ['nullable', 'numeric', 'min:0'],
            'max_cost' => ['nullable', 'numeric', 'min:0'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:255'],
            'pharmacist_name' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
