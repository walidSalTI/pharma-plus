<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'batch_number' => ['nullable', 'string', 'max:255'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'expiration_date' => ['nullable', 'date'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'batch_number' => ['description' => 'Updated batch identifier.'],
            'quantity' => ['description' => 'Updated batch quantity.'],
            'wholesale_price' => ['description' => 'Updated cost price per unit.'],
            'expiration_date' => ['description' => 'Updated expiration date (Y-m-d).'],
        ];
    }
}
