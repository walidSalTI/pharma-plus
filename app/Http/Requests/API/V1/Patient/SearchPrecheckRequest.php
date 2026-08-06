<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Patient;

use Illuminate\Foundation\Http\FormRequest;

class SearchPrecheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'queries' => 'required|array|min:1',
            'queries.*' => 'required|string|min:2',
        ];
    }

    public function messages(): array
    {
        return [
            'queries.required' => 'At least one search query is required.',
            'queries.*.required' => 'Each search query is required.',
            'queries.*.min' => 'Each search query must be at least 2 characters.',
        ];
    }
}
