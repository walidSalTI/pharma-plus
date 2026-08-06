<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Company;

use Illuminate\Foundation\Http\FormRequest;

class BatchScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedules' => ['required', 'array', 'min:1'],
            'schedules.*.rep_id' => ['required', 'uuid', 'exists:scientific_reps,id'],
            'schedules.*.doctor_id' => ['required', 'uuid', 'exists:doctors,id'],
            'schedules.*.scheduled_at' => ['required', 'date'],
            'schedules.*.notes' => ['nullable', 'string'],
        ];
    }
}
