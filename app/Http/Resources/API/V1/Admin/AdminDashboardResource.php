<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_users' => $this->resource['total_users'],
            'pending_verifications' => [
                'doctors' => $this->resource['pending_doctors'],
                'pharmacists' => $this->resource['pending_pharmacists'],
                'companies' => $this->resource['pending_companies'],
            ],
            'pending_proposals' => $this->resource['pending_proposals'],
        ];
    }
}
