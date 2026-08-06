<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\TwoFactor;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmTwoFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:6'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'code' => ['description' => '6-digit code from the authenticator app'],
        ];
    }
}
