<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class BulkImportInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'file' => ['description' => 'Excel file (.xlsx, .xls, .csv) with columns: Commercial Name, Active Ingredient, Concentration, Stock Quantity, Retail Price, Expiry Date'],
        ];
    }
}
