<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Patient;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'blood_type' => ['nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'f_name' => ['description' => 'First name of the patient'],
            'l_name' => ['description' => 'Last name of the patient'],
            'email' => ['description' => 'Email address (unique)'],
            'password' => ['description' => 'Password (min 8 chars, must match password_confirmation)'],
            'phone_number' => ['description' => 'Phone number'],
            'age' => ['description' => 'Age (must be 18+)'],
            'gender' => ['description' => 'Gender: male or female'],
            'location' => ['description' => 'Physical address'],
            'blood_type' => ['description' => 'Blood type (e.g. A+, O-)'],
            'latitude' => ['description' => 'Latitude coordinate'],
            'longitude' => ['description' => 'Longitude coordinate'],
        ];
    }
}
