<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApproveProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trade_name' => ['required', 'string', 'max:255', 'unique:products,name'],
            'barcode' => ['nullable', 'string', 'max:255', 'unique:products,barcode'],
            'form' => ['nullable', 'string', 'max:255'],
            'arabic_form' => ['nullable', 'string', 'max:255'],
            'manufacture_id' => ['nullable', 'string', 'exists:manufactures,id'],
            'usage_id' => ['nullable', 'string', 'exists:usages,id'],
            'image' => ['nullable', 'image'],
            'active_ingredients' => ['required', 'array', 'min:1'],
            'active_ingredients.*.active_ingredient_id' => ['required', 'string', 'exists:active_ingredients,id'],
            'active_ingredients.*.active_ratio' => ['nullable', 'string', 'max:255'],
        ];
    }
}
