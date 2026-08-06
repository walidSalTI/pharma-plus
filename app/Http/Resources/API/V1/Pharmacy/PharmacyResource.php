<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Pharmacy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PharmacyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'support_email' => $this->support_email,
            'support_number' => $this->support_number,
            'front_image' => $this->front_image ? asset('storage/'.$this->front_image) : null,
            'operating_hours' => OperatingHourResource::collection($this->whenLoaded('pharmacyOperatingHours')),
            'average_rating' => $this->whenAggregated('pharmacyReviews', 'rating', 'avg'),
            'reviews_count' => $this->whenAggregated('pharmacyReviews', 'rating', 'count'),
            'staff_count' => $this->staff_pharmacists_count ?? 0,
            'pending_orders_count' => $this->pending_orders_count ?? 0,
            'low_stock_count' => $this->low_stock_count ?? 0,
            'total_stock' => $this->total_stock ?? 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
