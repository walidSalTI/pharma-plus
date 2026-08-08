<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class SlowMovingReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }

    public function queryParameters(): array
    {
        return [
            'start_date' => ['description' => 'Analysis window start (Y-m-d); default is end_date minus days'],
            'end_date' => ['description' => 'Analysis window end (Y-m-d); default is today'],
            'days' => ['description' => 'Inactivity threshold in days (default 90)'],
        ];
    }
}
