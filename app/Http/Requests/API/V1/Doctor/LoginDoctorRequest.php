<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class LoginDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
