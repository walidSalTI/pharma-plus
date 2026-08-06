<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryRequest extends FormRequest
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
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
            'items.*.min_stock' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'items' => ['description' => 'Array of inventory items to update'],
            'items[].medication_id' => ['description' => 'UUID of the medication to update'],
            'items[].price' => ['description' => 'Updated retail selling price'],
            'items[].min_stock' => ['description' => 'Updated minimum stock threshold'],
        ];
    }
}
