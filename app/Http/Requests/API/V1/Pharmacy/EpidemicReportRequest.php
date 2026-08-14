<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class EpidemicReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
