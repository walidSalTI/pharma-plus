<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Specialist;

use Illuminate\Foundation\Http\FormRequest;

class StoreRiskMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chronic_disease_id' => ['required', 'string', 'exists:chronic_diseases,id'],
            'active_ingredient_id' => ['required', 'string', 'exists:active_ingredients,id'],
            'risk_level' => ['required', 'integer', 'in:0,1,2'],
        ];
    }
}
