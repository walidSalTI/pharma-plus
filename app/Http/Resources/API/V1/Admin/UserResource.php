<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'f_name' => $this->f_name,
            'l_name' => $this->l_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'age' => $this->age,
            'gender' => $this->gender,
            'location' => $this->location,
            'roles' => $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'doctor' => $this->whenLoaded('doctor', fn () => ['id' => $this->doctor->id, 'specialization' => $this->doctor->specialization]),
            'pharmacist' => $this->whenLoaded('pharmacist', fn () => ['id' => $this->pharmacist->id, 'verification_status' => $this->pharmacist->verification_status]),
            'patient' => $this->whenLoaded('patient', fn () => ['id' => $this->patient->id]),
            'specialist' => $this->whenLoaded('specialist', fn () => ['id' => $this->specialist->id, 'specialization' => $this->specialist->specialization]),
            'scientific_rep' => $this->whenLoaded('scientificRep', fn () => ['id' => $this->scientificRep->id, 'company_id' => $this->scientificRep->company_id]),
            'company' => $this->whenLoaded('pharmaceuticalCompany', fn () => ['id' => $this->pharmaceuticalCompany->id, 'status' => $this->pharmaceuticalCompany->status]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
