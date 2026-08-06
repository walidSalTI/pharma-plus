<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'string', 'exists:products,id'],
            'manufacture_id' => ['nullable', 'string', 'exists:manufactures,id'],
            'usage_id' => ['nullable', 'string', 'exists:usages,id'],
            'status' => ['sometimes', 'string', 'in:pending,accepted,rejected'],
            'form' => ['nullable', 'string', 'max:255'],
            'arabic_form' => ['nullable', 'string', 'max:255'],
        ];
    }
}
