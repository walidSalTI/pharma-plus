<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Company;

use Illuminate\Foundation\Http\FormRequest;

class RegisterCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // User fields
            'f_name' => ['required', 'string', 'max:255'],
            'l_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone_number' => ['required', 'string', 'max:20'],
            'age' => ['required', 'integer', 'min:18', 'max:120'],
            'gender' => ['required', 'in:male,female'],
            'location' => ['nullable', 'string', 'max:500'],
            // Company fields
            'commercial_name' => ['required', 'string', 'max:255'],
            'commercial_registration' => ['required', 'string', 'max:255', 'unique:pharmaceutical_companies,commercial_registration'],
            'address' => ['required', 'string', 'max:500'],
            'phone' => ['required', 'string', 'max:20'],
            'license_number' => ['required', 'string', 'max:255', 'unique:pharmaceutical_companies,license_number'],
            'license_image' => ['required', 'image', 'max:4096'],
        ];
    }
}
