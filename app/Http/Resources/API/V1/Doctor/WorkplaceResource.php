<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Doctor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkplaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'place_name' => $this->place_name,
            'place_type' => $this->place_type,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'radius_meters' => $this->radius_meters,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
