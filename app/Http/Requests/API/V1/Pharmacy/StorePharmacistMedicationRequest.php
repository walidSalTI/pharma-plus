<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class StorePharmacistMedicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trade_name' => ['required', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255', 'unique:products,barcode'],
            'form' => ['required', 'string', 'max:255'],
            'arabic_form' => ['nullable', 'string', 'max:255'],
            'manufacture_id' => ['nullable', 'string', 'exists:manufactures,id'],
            'image' => ['nullable', 'string', 'max:255'],
        ];
    }
}
