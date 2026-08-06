<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class StoreProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'medication_name' => ['required', 'string', 'max:255'],
            'form' => ['required', 'string', 'max:255'],
            'image_url' => ['nullable', 'image', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'medication_name' => ['description' => 'Proposed commercial/brand name of the missing drug'],
            'form' => ['description' => 'Dosage form (e.g. tablet, capsule, syrup, injection)'],
            'image_url' => ['description' => 'Photo or PDF of the inner pamphlet/leaflet'],
        ];
    }
}
