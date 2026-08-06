<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Company;

use Illuminate\Http\Request;

class RepDetailResource extends RepResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        $total = (int) ($this->representative_visits_count ?? 0);
        $verified = (int) ($this->verified_visits_count ?? 0);

        $data['stats'] = [
            'total_visits' => $total,
            'verified_visits' => $verified,
            'failed_visits' => max(0, $total - $verified),
            'adherence_rate' => $total > 0 ? round(($verified / $total) * 100, 2) : 0,
        ];
        $data['assignments_count'] = $this->doctor_assignments_count ?? 0;
        $data['schedules_count'] = $this->weekly_schedules_count ?? 0;

        return $data;
    }
}
