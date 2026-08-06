<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Rep;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RepStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_visits' => $this->resource['total_visits'],
            'verified_visits' => $this->resource['verified_visits'],
            'failed_visits' => $this->resource['failed_visits'],
            'adherence_rate' => $this->resource['adherence_rate'],
        ];
    }
}
