<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\TwoFactor;

use Illuminate\Foundation\Http\FormRequest;

class DisableTwoFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string'],
            'code' => ['required', 'string', 'size:6'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'password' => ['description' => 'Current account password for verification'],
            'code' => ['description' => '6-digit code from the authenticator app'],
        ];
    }
}
