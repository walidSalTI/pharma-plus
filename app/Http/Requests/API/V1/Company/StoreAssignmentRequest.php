<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Company;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rep_id' => ['required', 'uuid', 'exists:scientific_reps,id'],
            'doctor_id' => ['required', 'uuid', 'exists:doctors,id'],
        ];
    }
}
