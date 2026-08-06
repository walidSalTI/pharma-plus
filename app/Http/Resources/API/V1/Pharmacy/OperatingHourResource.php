<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Pharmacy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;

class OperatingHourResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'day_of_week' => $this->day_of_week,
            'day_name' => match ((int) $this->day_of_week) {
                0 => 'Sunday',
                1 => 'Monday',
                2 => 'Tuesday',
                3 => 'Wednesday',
                4 => 'Thursday',
                5 => 'Friday',
                6 => 'Saturday',
                default => 'Unknown',
            },
            'opening_time' => $this->opening_time ? Date::parse($this->opening_time)->format('H:i') : null,
            'closing_time' => $this->closing_time ? Date::parse($this->closing_time)->format('H:i') : null,
            'is_24_hours' => (bool) $this->is_24_hours,
            'is_closed' => (bool) $this->is_closed,
        ];
    }
}
