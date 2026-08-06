<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Rep;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVisitNotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
