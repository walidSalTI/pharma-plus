<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Doctor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDoctorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'f_name' => ['sometimes', 'string', 'max:255'],
            'l_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($this->user()?->id)],
            'phone_number' => ['sometimes', 'string', 'max:20'],
            'age' => ['sometimes', 'integer', 'min:18', 'max:120'],
            'gender' => ['sometimes', 'in:male,female'],
            'location' => ['sometimes', 'nullable', 'string', 'max:500'],
            'specialization' => ['sometimes', 'string', 'max:255'],
            'syndicate_card_image' => ['sometimes', 'nullable', 'image', 'max:2048'],
        ];
    }
}
