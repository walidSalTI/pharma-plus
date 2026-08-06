<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Pharmacy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient' => $this->whenLoaded('patient', fn () => [
                'id' => $this->patient->id,
                'name' => $this->patient->user?->f_name.' '.$this->patient->user?->l_name,
            ]),
            'rating' => $this->rating,
            'availability_rating' => $this->availability_rating,
            'comment' => $this->comment,
            'pharmacist_reply' => $this->pharmacist_reply,
            'created_at' => $this->created_at,
        ];
    }
}
