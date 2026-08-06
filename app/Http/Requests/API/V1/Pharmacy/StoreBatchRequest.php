<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class StoreBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'batch_number' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'wholesale_price' => ['required', 'numeric', 'min:0'],
            'expiration_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'batch_number' => ['description' => 'Optional batch identifier. Auto-generated if omitted.'],
            'quantity' => ['description' => 'Initial stock quantity for this batch.'],
            'wholesale_price' => ['description' => 'Cost price per unit for this batch.'],
            'expiration_date' => ['description' => 'Batch expiration date (Y-m-d).'],
        ];
    }
}
