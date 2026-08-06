<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Specialist;

use Illuminate\Foundation\Http\FormRequest;

class StoreChronicDiseaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:chronic_diseases,code'],
            'category' => ['nullable', 'string', 'max:255'],
        ];
    }
}
