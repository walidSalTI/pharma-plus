<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Patient;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePillsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->patient !== null;
    }

    public function rules(): array
    {
        return [
            'available_pills' => ['required', 'integer', 'min:0'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'available_pills' => ['description' => 'New pill count (must be 0 or more)'],
        ];
    }
}
