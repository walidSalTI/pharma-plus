<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Company;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_reps' => $this->resource['total_reps'],
            'total_assignments' => $this->resource['total_assignments'],
            'total_schedules' => $this->resource['total_schedules'],
            'completed_visits' => $this->resource['completed_visits'],
            'verified_visits' => $this->resource['verified_visits'],
            'failed_visits' => $this->resource['failed_visits'],
            'adherence_rate' => $this->resource['adherence_rate'],
        ];
    }
}
