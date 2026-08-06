<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Specialist;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProposalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'medication_name' => $this->medication_name,
            'form' => $this->form,
            'image_url' => $this->image_url ? asset('storage/'.$this->image_url) : null,
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'pharmacist' => $this->whenLoaded('pharmacist', fn () => [
                'id' => $this->pharmacist->id,
                'name' => $this->pharmacist->user?->f_name.' '.$this->pharmacist->user?->l_name,
            ]),
            'specialist' => $this->whenLoaded('specialist', fn () => [
                'id' => $this->specialist->id,
                'name' => $this->specialist->user?->f_name.' '.$this->specialist->user?->l_name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
