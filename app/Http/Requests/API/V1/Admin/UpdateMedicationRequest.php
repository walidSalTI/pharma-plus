<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $this->route('medication')?->id;

        return [
            'product_id' => ['sometimes', 'string', 'exists:products,id'],
            'manufacture_id' => ['nullable', 'string', 'exists:manufactures,id'],
            'usage_id' => ['sometimes', 'nullable', 'string', 'exists:usages,id'],
            'status' => ['sometimes', 'string', 'in:pending,accepted,rejected'],
            'form' => ['nullable', 'string', 'max:255'],
            'arabic_form' => ['nullable', 'string', 'max:255'],
        ];
    }
}
