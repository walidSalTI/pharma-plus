<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class RegisterDoctorRequest extends FormRequest
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
            'specialization' => ['required', 'string', 'max:255'],
            'syndicate_card_image' => ['nullable', 'image', 'max:2048'],
            'workplaces' => ['nullable', 'array'],
            'workplaces.*.place_name' => ['required_with:workplaces', 'string', 'max:255'],
            'workplaces.*.place_type' => ['required_with:workplaces', 'in:clinic,hospital,medical_center'],
            'workplaces.*.latitude' => ['required_with:workplaces', 'numeric', 'between:-90,90'],
            'workplaces.*.longitude' => ['required_with:workplaces', 'numeric', 'between:-180,180'],
            'workplaces.*.radius_meters' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ];
    }
}
