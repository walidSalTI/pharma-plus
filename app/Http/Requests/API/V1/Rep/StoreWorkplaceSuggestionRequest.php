<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Rep;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkplaceSuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id' => ['required', 'uuid', 'exists:doctors,id'],
            'place_name' => ['required', 'string', 'max:255'],
            'place_type' => ['required', 'in:clinic,hospital,medical_center'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ];
    }
}
