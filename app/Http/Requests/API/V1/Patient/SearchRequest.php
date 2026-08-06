<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Patient;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'queries' => 'required|array|min:1',
            'queries.*' => 'required|string|min:2',
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'query.required' => 'Search query is required.',
            'latitude.required' => 'Patient latitude is required for geospatial filtering.',
            'latitude.between' => 'Latitude must be between -90 and 90.',
            'longitude.required' => 'Patient longitude is required for geospatial filtering.',
            'longitude.between' => 'Longitude must be between -180 and 180.',
        ];
    }
}
