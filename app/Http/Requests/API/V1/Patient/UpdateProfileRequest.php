<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Patient;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->patient !== null;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'f_name' => ['sometimes', 'string', 'max:255'],
            'l_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,'.$userId],
            'phone_number' => ['sometimes', 'string', 'max:20'],
            'age' => ['sometimes', 'integer', 'min:1', 'max:150'],
            'gender' => ['sometimes', 'in:male,female'],
            'location' => ['sometimes', 'nullable', 'string', 'max:500'],
            'blood_type' => ['sometimes', 'nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'f_name' => ['description' => 'First name of the patient'],
            'l_name' => ['description' => 'Last name of the patient'],
            'email' => ['description' => 'Email address (unique, ignores current user)'],
            'phone_number' => ['description' => 'Phone number'],
            'age' => ['description' => 'Age'],
            'gender' => ['description' => 'Gender: male or female'],
            'location' => ['description' => 'Physical address'],
            'blood_type' => ['description' => 'Blood type (e.g. A+, O-)'],
            'latitude' => ['description' => 'Latitude coordinate'],
            'longitude' => ['description' => 'Longitude coordinate'],
        ];
    }
}
