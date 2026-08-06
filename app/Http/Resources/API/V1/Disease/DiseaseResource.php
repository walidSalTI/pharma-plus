<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Disease;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiseaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'category' => $this->category,
            'active_ingredients' => $this->whenLoaded(
                'activeIngredients',
                fn () => $this->activeIngredients->map(fn ($ingredient) => [
                    'id' => $ingredient->id,
                    'ingredient_name_en' => $ingredient->ingredient_name_en,
                    'pivot' => [
                        'risk_level' => $ingredient->pivot?->risk_level,
                        'is_ai_verified' => $ingredient->pivot?->is_ai_verified,
                        'conflict_reason' => $ingredient->pivot?->conflict_reason,
                        'ai_explanation' => $ingredient->pivot?->ai_explanation,
                    ],
                ])
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
