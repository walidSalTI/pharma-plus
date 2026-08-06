<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Patient;

use Illuminate\Foundation\Http\FormRequest;

class HoldMedicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->patient !== null;
    }

    public function rules(): array
    {
        return [
            'pharmacy_id' => ['required', 'string', 'exists:pharmacies,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medication_id' => ['required', 'string', 'exists:medications,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'pharmacist_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
