<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Patient;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicationWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->patient !== null;
    }

    public function rules(): array
    {
        return [
            'state' => ['sometimes', 'string', 'in:permanent,temporary'],
            'dosage' => ['sometimes', 'string', 'max:255'],
            'available_pills' => ['nullable', 'integer', 'min:0'],
            'frequency' => ['sometimes', 'string', 'in:daily,specific_days,as_needed'],
            'instructions_before' => ['nullable', 'string', 'max:2000'],
            'instructions_after' => ['nullable', 'string', 'max:2000'],
            'start_date' => ['nullable', 'date', 'before_or_equal:end_date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['sometimes', 'boolean'],
            'schedules' => ['nullable', 'array', 'min:1'],
            'schedules.*.dose_time' => ['required_with:schedules', 'date_format:H:i'],
            'schedules.*.day_of_week' => ['nullable', 'integer', 'between:0,6'],
        ];
    }
}
