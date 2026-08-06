<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'f_name' => ['sometimes', 'string', 'max:255'],
            'l_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,'.$userId],
            'phone_number' => ['sometimes', 'string', 'max:50'],
            'age' => ['sometimes', 'integer', 'min:1', 'max:150'],
            'gender' => ['sometimes', 'string', 'in:male,female'],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }
}
