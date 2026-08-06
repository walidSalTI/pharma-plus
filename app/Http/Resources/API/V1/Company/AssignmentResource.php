<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Company;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'rep_id' => $this->rep_id,
            'doctor_id' => $this->doctor_id,
            'rep_name' => $this->whenLoaded('scientificRep', fn () => trim($this->scientificRep->user->f_name.' '.$this->scientificRep->user->l_name)),
            'doctor_name' => $this->whenLoaded('doctor', fn () => trim($this->doctor->user->f_name.' '.$this->doctor->user->l_name)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
