<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'specialization' => ['required', 'string', 'max:255'],
            'verification_status' => ['sometimes', 'string', 'in:unverified,pending,approved,rejected'],
        ];
    }
}
