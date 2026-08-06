<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'f_name' => ['nullable', 'string', 'max:255'],
            'l_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'unique:users,email,'.$this->user()?->id],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'age' => ['nullable', 'integer', 'min:18', 'max:120'],
            'gender' => ['nullable', 'in:male,female'],
            'location' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'f_name' => ['description' => 'First name'],
            'l_name' => ['description' => 'Last name'],
            'email' => ['description' => 'Email address (unique)'],
            'phone_number' => ['description' => 'Phone number'],
            'age' => ['description' => 'Age (must be 18+)'],
            'gender' => ['description' => 'Gender: male or female'],
            'location' => ['description' => 'Physical address'],
        ];
    }
}
