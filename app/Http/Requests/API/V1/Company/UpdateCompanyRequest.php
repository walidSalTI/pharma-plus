<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()?->pharmaceuticalCompany?->id;

        return [
            'commercial_name' => ['sometimes', 'string', 'max:255'],
            'commercial_registration' => ['sometimes', 'string', 'max:255', Rule::unique('pharmaceutical_companies', 'commercial_registration')->ignore($companyId)],
            'address' => ['sometimes', 'string', 'max:500'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'license_number' => ['sometimes', 'string', 'max:255', Rule::unique('pharmaceutical_companies', 'license_number')->ignore($companyId)],
            'license_image' => ['sometimes', 'image', 'max:4096'],
        ];
    }
}
