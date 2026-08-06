<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChronicDiseaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'chronic_disease_id' => $this->chronic_disease_id,
            'disease_name_en' => $this->chronicDisease?->name_en,
            'disease_name_ar' => $this->chronicDisease?->name_ar,
            'disease_code' => $this->chronicDisease?->code,
            'disease_category' => $this->chronicDisease?->category,
            'diagnosis_year' => $this->diagnosis_year,
            'severity' => $this->severity,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
