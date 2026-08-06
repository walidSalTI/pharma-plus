<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Rep;

use Illuminate\Foundation\Http\FormRequest;

class CheckInVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id' => ['required', 'uuid', 'exists:doctors,id'],
            'code' => ['required', 'string'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'schedule_id' => ['required', 'uuid', 'exists:weekly_schedules,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
