<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class DemandReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'radius' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'group_by' => ['nullable', 'string', 'in:product,ingredient,region'],
            'limit' => ['nullable', 'integer', 'in:5,10,25,50'],
        ];
    }

    public function queryParameters(): array
    {
        return [
            'start_date' => ['description' => 'Report start date (Y-m-d)'],
            'end_date' => ['description' => 'Report end date (Y-m-d), must be >= start_date'],
            'radius' => ['description' => 'Search radius in km around the pharmacy (default 10)'],
            'group_by' => ['description' => 'Grouping mode: product, ingredient, or region (default product)'],
            'limit' => ['description' => 'Number of items to return (default 10)'],
        ];
    }
}
