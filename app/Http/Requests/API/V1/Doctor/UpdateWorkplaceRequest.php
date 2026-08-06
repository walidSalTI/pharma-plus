<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Doctor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkplaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'place_name' => ['sometimes', 'string', 'max:255'],
            'place_type' => ['sometimes', 'in:clinic,hospital,medical_center'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            'radius_meters' => ['sometimes', 'integer', 'min:1', 'max:10000'],
        ];
    }
}
