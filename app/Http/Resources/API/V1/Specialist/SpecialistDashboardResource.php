<?php

declare(strict_types=1);

namespace App\Http\Resources\API\V1\Specialist;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpecialistDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'specialist' => $this->resource['specialist'],
            'pending_proposals_count' => $this->resource['pending_proposals_count'],
            'recent_proposals' => ProposalResource::collection($this->resource['recent_proposals']),
        ];
    }
}
