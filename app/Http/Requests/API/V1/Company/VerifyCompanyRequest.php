<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Company;

use Illuminate\Foundation\Http\FormRequest;

class VerifyCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:active,rejected,suspended'],
            'rejection_reason' => ['required_if:status,rejected', 'nullable', 'string', 'max:1000'],
        ];
    }
}
