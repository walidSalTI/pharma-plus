<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Patient;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicationWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->patient !== null;
    }

    public function rules(): array
    {
        return [
            'medication_id' => ['required', 'string', 'exists:medications,id'],
            'state' => ['required', 'string', 'in:permanent,temporary'],
            'chronic_id' => ['nullable', 'string', 'exists:chronic_records,id'],
            'dosage' => ['required', 'string', 'max:255'],
            'available_pills' => ['nullable', 'integer', 'min:0'],
            'frequency' => ['required', 'string', 'in:daily,specific_days,as_needed'],
            'instructions_before' => ['nullable', 'string', 'max:2000'],
            'instructions_after' => ['nullable', 'string', 'max:2000'],
            'start_date' => ['nullable', 'date', 'before_or_equal:end_date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['required', 'boolean'],
            'schedules' => ['required_if:frequency,daily,specific_days', 'nullable', 'array', 'min:1'],
            'schedules.*.dose_time' => ['required_with:schedules', 'date_format:H:i'],
            'schedules.*.day_of_week' => ['nullable', 'integer', 'between:0,6'],
        ];
    }

    public function messages(): array
    {
        return [
            'schedules.required_if' => 'At least one schedule time is required for daily or specific_days frequency.',
            'schedules.*.dose_time.required_with' => 'Each schedule must have a dose_time.',
            'schedules.*.dose_time.date_format' => 'Dose time must be in format HH:MM.',
            'schedules.*.day_of_week.between' => 'Day of week must be between 0 (Sunday) and 6 (Saturday).',
        ];
    }
}
