<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\TwoFactor;

use Illuminate\Foundation\Http\FormRequest;

class VerifyTwoFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'two_factor_token' => ['required', 'string'],
            'code' => ['required_without:recovery_code', 'string', 'size:6'],
            'recovery_code' => ['required_without:code', 'string'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'two_factor_token' => ['description' => 'Encrypted token returned by the login endpoint when 2FA is required'],
            'code' => ['description' => '6-digit code from the authenticator app (required if recovery_code not provided)'],
            'recovery_code' => ['description' => 'One-time recovery code (required if code not provided)'],
        ];
    }
}
