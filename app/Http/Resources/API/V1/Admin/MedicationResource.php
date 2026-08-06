<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'trade_name' => $this->product?->name,
            'barcode' => $this->product?->barcode,
            'form' => $this->form,
            'arabic_form' => $this->arabic_form,
            'image' => $this->product?->image ? asset('storage/'.$this->product->image) : null,
            'manufacture' => $this->whenLoaded('manufacture', fn () => [
                'id' => $this->manufacture->id,
                'name' => $this->manufacture->name,
            ]),
            'category' => $this->whenLoaded('usage', fn () => $this->usage->title?->category ? [
                'id' => $this->usage->title->category->id,
                'name' => $this->usage->title->category->name,
            ] : null),
            'title' => $this->whenLoaded('usage', fn () => $this->usage->title ? [
                'id' => $this->usage->title->id,
                'name' => $this->usage->title->name,
            ] : null),
            'usage' => $this->whenLoaded('usage', fn () => [
                'id' => $this->usage->id,
                'name' => $this->usage->name,
            ]),
            'usage_id' => $this->usage_id,
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'active_ingredients' => $this->whenLoaded('activeIngredients', fn () => $this->activeIngredients->map(fn ($ingredient) => [
                'id' => $ingredient->id,
                'name' => $ingredient->ingredient_name_en,
                'ratio' => $ingredient->pivot->active_ratio,
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
