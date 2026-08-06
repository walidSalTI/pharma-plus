<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertOperatingHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'hours.*.opening_time' => [
                Rule::requiredIf(fn () => ! request()->input('hours.*.is_closed') && ! request()->input('hours.*.is_24_hours')),
                'nullable',
                'date_format:H:i',
            ],
            'hours.*.closing_time' => [
                'nullable',
                'date_format:H:i',
                'after:hours.*.opening_time',
            ],
            'hours.*.is_24_hours' => ['nullable', 'boolean'],
            'hours.*.is_closed' => ['nullable', 'boolean'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'hours' => ['description' => 'Array of 7 day objects (0=Sunday .. 6=Saturday)'],
            'hours.*.day_of_week' => ['description' => 'Day index (0-6)'],
            'hours.*.opening_time' => ['description' => 'Opening time in HH:MI format'],
            'hours.*.closing_time' => ['description' => 'Closing time in HH:MI format'],
            'hours.*.is_24_hours' => ['description' => 'Open 24 hours on this day'],
            'hours.*.is_closed' => ['description' => 'Closed on this day'],
        ];
    }
}
