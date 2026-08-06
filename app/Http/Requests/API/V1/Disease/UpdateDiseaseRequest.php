<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Disease;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDiseaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $diseaseId = $this->route('disease')?->id ?? $this->route('disease');

        return [
            'code' => ['nullable', 'string', 'max:50', 'unique:chronic_diseases,code,'.$diseaseId],
            'name_ar' => ['sometimes', 'string', 'max:255'],
            'name_en' => ['sometimes', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'code' => ['description' => 'Unique disease code (e.g. ICD-10)'],
            'name_ar' => ['description' => 'Disease name in Arabic'],
            'name_en' => ['description' => 'Disease name in English'],
            'category' => ['description' => 'Disease classification category'],
        ];
    }
}
