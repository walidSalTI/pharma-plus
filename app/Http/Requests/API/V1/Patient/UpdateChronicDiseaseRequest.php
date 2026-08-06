<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Patient;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChronicDiseaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->patient !== null;
    }

    public function rules(): array
    {
        return [
            'diagnosis_year' => ['sometimes', 'integer', 'min:1900', 'max:'.date('Y')],
            'severity' => ['nullable', 'string', 'max:255'],
        ];
    }
}
