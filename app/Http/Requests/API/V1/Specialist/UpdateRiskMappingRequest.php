<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Specialist;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRiskMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'risk_level' => ['required', 'integer', 'in:0,1,2'],
        ];
    }
}
