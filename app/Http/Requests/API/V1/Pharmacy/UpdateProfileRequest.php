<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'support_number' => ['nullable', 'string', 'max:20'],
            'front_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => ['description' => 'Store display name'],
            'address' => ['description' => 'Physical street address'],
            'latitude' => ['description' => 'Geographic latitude coordinate'],
            'longitude' => ['description' => 'Geographic longitude coordinate'],
            'support_email' => ['description' => 'Customer support email'],
            'support_number' => ['description' => 'Customer support phone number'],
            'front_image' => ['description' => 'Storefront image'],
        ];
    }
}
