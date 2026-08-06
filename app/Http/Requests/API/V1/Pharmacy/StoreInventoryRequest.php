<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.medication_id' => ['required', 'string', 'exists:medications,id'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.min_stock' => ['nullable', 'integer', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:255'],
            'items.*.wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.stock' => ['nullable', 'integer', 'min:0'],
            'items.*.expiration_date' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'items' => ['description' => 'Array of inventory items to add'],
            'items[].medication_id' => ['description' => 'UUID of the medication from the central catalog'],
            'items[].price' => ['description' => 'Retail selling price'],
            'items[].min_stock' => ['description' => 'Minimum stock threshold for reorder alerts'],
            'items[].batch_number' => ['description' => 'Optional batch identifier. Auto-generated if omitted.'],
            'items[].wholesale_price' => ['description' => 'Cost price from supplier (for batch)'],
            'items[].stock' => ['description' => 'Initial batch quantity'],
            'items[].expiration_date' => ['description' => 'Batch expiration date (Y-m-d)'],
        ];
    }
}
