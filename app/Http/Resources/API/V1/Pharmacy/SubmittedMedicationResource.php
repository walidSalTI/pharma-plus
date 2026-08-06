<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Pharmacy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmittedMedicationResource extends JsonResource
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
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'manufacture' => $this->whenLoaded('manufacture', fn () => [
                'id' => $this->manufacture->id,
                'name' => $this->manufacture->name,
            ]),
            'usage' => $this->whenLoaded('usage', fn () => [
                'id' => $this->usage->id,
                'name' => $this->usage->name,
            ]),
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'barcode' => $this->product->barcode,
                'image' => $this->product->image ? asset('storage/'.$this->product->image) : null,
                'type' => $this->product->type,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
