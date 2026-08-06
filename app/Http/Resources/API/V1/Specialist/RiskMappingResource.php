<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Specialist;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiskMappingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'chronic_disease' => $this->whenLoaded('chronicDisease', fn () => [
                'id' => $this->chronicDisease->id,
                'name_ar' => $this->chronicDisease->name_ar,
                'name_en' => $this->chronicDisease->name_en,
            ]),
            'active_ingredient' => $this->whenLoaded('activeIngredient', fn () => [
                'id' => $this->activeIngredient->id,
                'name' => $this->activeIngredient->ingredient_name_en,
            ]),
            'risk_level' => $this->risk_level,
            'is_ai_verified' => $this->is_ai_verified,
            'conflict_reason' => $this->conflict_reason,
            'ai_explanation' => $this->ai_explanation,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
