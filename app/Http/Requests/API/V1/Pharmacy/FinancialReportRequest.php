<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class FinancialReportRequest extends FormRequest
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
            'type' => ['nullable', 'string', 'in:financial,inventory,full'],
        ];
    }

    public function queryParameters(): array
    {
        return [
            'start_date' => ['description' => 'Report start date (Y-m-d)'],
            'end_date' => ['description' => 'Report end date (Y-m-d), must be >= start_date'],
            'type' => ['description' => 'AI analysis type (financial, inventory, full). Only used by the AI insights endpoints.'],
        ];
    }
}
