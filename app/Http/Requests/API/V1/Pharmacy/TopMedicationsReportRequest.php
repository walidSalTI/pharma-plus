<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class TopMedicationsReportRequest extends FormRequest
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
            'limit' => ['nullable', 'integer', 'in:5,10'],
        ];
    }

    public function queryParameters(): array
    {
        return [
            'start_date' => ['description' => 'Report start date (Y-m-d)'],
            'end_date' => ['description' => 'Report end date (Y-m-d), must be >= start_date'],
            'limit' => ['description' => 'Number of medications to return (5 or 10, default 10)'],
        ];
    }
}
