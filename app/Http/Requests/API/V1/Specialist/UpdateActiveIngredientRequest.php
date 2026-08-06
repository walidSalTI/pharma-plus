<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Specialist;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActiveIngredientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ingredient_name_en' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
