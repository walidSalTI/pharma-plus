<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Rep;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Rep\StoreWorkplaceSuggestionRequest;
use App\Http\Resources\API\V1\Doctor\WorkplaceResource;
use App\Models\DoctorWorkplace;
use Illuminate\Http\JsonResponse;

class WorkplaceSuggestionController extends Controller
{
    /**
     * Store workplace suggestion.
     *
     * Confirm the rep is assigned to the doctor, then add the suggested
     * geofence as a doctor workplace because no separate suggestion table exists.
     */
    public function store(StoreWorkplaceSuggestionRequest $request): JsonResponse
    {
        $rep = $request->user()->scientificRep;

        if (! $rep) {
            return response()->json(['message' => 'Representative profile not found.'], 404);
        }

        $validated = $request->validated();
        if (! $rep->doctorAssignments()->where('doctor_id', $validated['doctor_id'])->exists()) {
            return response()->json(['message' => 'Representative is not assigned to this doctor.'], 422);
        }

        $workplace = DoctorWorkplace::create([
            'doctor_id' => $validated['doctor_id'],
            'place_name' => $validated['place_name'],
            'place_type' => $validated['place_type'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'radius_meters' => $validated['radius_meters'] ?? 50,
        ]);

        return response()->json([
            'message' => 'Workplace suggestion saved successfully.',
            'data' => new WorkplaceResource($workplace),
        ], 201);
    }
}
