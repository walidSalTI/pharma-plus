<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class FindPosItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->pharmacist !== null;
    }

    public function rules(): array
    {
        return [
            'search' => ['required', 'string', 'min:2'],
        ];
    }

    public function messages(): array
    {
        return [
            'search.required' => 'Search query is required.',
            'search.min' => 'Search query must be at least 2 characters.',
        ];
    }
}
