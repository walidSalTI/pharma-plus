<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Rep;

use Illuminate\Http\Request;

class VisitDetailResource extends VisitResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        $data['doctor'] = $this->whenLoaded('doctor', fn () => [
            'id' => $this->doctor->id,
            'name' => trim($this->doctor->user->f_name.' '.$this->doctor->user->l_name),
            'specialization' => $this->doctor->specialization,
        ]);
        $data['schedule'] = $this->whenLoaded('weeklySchedule', fn () => [
            'id' => $this->weeklySchedule->id,
            'scheduled_at' => $this->weeklySchedule->scheduled_at,
            'status' => $this->weeklySchedule->status,
        ]);

        return $data;
    }
}
