<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Company;

use Illuminate\Foundation\Http\FormRequest;

class StoreRepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'f_name' => ['required', 'string', 'max:255'],
            'l_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone_number' => ['required', 'string', 'max:20'],
            'age' => ['required', 'integer', 'min:18', 'max:120'],
            'gender' => ['required', 'in:male,female'],
            'location' => ['nullable', 'string', 'max:500'],
        ];
    }
}
