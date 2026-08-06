<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Rep;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RepDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'rep' => $this->resource['rep'],
            'today_schedules' => RepScheduleResource::collection($this->resource['today_schedules']),
            'today_by_status' => $this->resource['today_by_status'],
            'weekly_overview' => $this->resource['weekly_overview'],
        ];
    }
}
