<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Patient;

use Illuminate\Foundation\Http\FormRequest;

class StoreChronicDiseaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->patient !== null;
    }

    public function rules(): array
    {
        return [
            'chronic_disease_id' => ['required', 'string', 'exists:chronic_diseases,id'],
            'diagnosis_year' => ['required', 'integer', 'min:1900', 'max:'.date('Y')],
            'severity' => ['nullable', 'string', 'max:255'],
        ];
    }
}
