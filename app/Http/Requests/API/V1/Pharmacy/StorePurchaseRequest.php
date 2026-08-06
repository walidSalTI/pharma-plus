<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->pharmacist !== null;
    }

    public function rules(): array
    {
        return [
            'supplier_name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:550'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medication_id' => ['required', 'string', 'exists:medications,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.wholesale_price' => ['required', 'numeric', 'min:0'],
            'items.*.expiration_date' => ['required', 'date', 'after:today'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_name.required' => 'Supplier name is required.',
            'items.required' => 'At least one item is required.',
            'items.min' => 'At least one item is required.',
            'items.*.medication_id.required' => 'Medication ID is required for each item.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'items.*.wholesale_price.required' => 'Wholesale price is required for each item.',
            'items.*.expiration_date.required' => 'Expiration date is required for each item.',
            'items.*.expiration_date.after' => 'Expiration date must be in the future.',
        ];
    }
}
