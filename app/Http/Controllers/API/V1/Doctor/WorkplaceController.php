<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Doctor\StoreWorkplaceRequest;
use App\Http\Requests\API\V1\Doctor\UpdateWorkplaceRequest;
use App\Http\Resources\API\V1\Doctor\WorkplaceResource;
use App\Models\DoctorWorkplace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkplaceController extends Controller
{
    /**
     * List doctor workplaces.
     *
     * Resolve the authenticated doctor and return every saved geofence
     * using the workplace API resource.
     */
    public function index(Request $request): JsonResponse
    {
        $doctor = $request->user()->doctor;

        if (! $doctor) {
            return response()->json(['message' => 'Doctor profile not found.'], 404);
        }

        return response()->json([
            'data' => WorkplaceResource::collection($doctor->doctorWorkplaces()->latest()->get()),
        ]);
    }

    /**
     * Store doctor workplace.
     *
     * Create a geofenced workplace owned by the authenticated doctor.
     * Radius defaults to 50 meters when the client omits it.
     */
    public function store(StoreWorkplaceRequest $request): JsonResponse
    {
        $doctor = $request->user()->doctor;

        if (! $doctor) {
            return response()->json(['message' => 'Doctor profile not found.'], 404);
        }

        $validated = $request->validated();
        $workplace = DoctorWorkplace::create([
            'doctor_id' => $doctor->id,
            'place_name' => $validated['place_name'],
            'place_type' => $validated['place_type'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'radius_meters' => $validated['radius_meters'] ?? 50,
        ]);

        return response()->json([
            'message' => 'Workplace created successfully.',
            'data' => new WorkplaceResource($workplace),
        ], 201);
    }

    /**
     * Update doctor workplace.
     *
     * Ensure the workplace belongs to the authenticated doctor before
     * applying the validated partial geofence update.
     */
    public function update(UpdateWorkplaceRequest $request, DoctorWorkplace $workplace): JsonResponse
    {
        $doctor = $request->user()->doctor;

        if (! $doctor || $workplace->doctor_id !== $doctor->id) {
            return response()->json(['message' => 'Workplace not found.'], 404);
        }

        $workplace->update($request->validated());

        return response()->json([
            'message' => 'Workplace updated successfully.',
            'data' => new WorkplaceResource($workplace->fresh()),
        ]);
    }

    /**
     * Delete doctor workplace.
     *
     * Ensure ownership, delete the geofence, and return a success response.
     */
    public function destroy(Request $request, DoctorWorkplace $workplace): JsonResponse
    {
        $doctor = $request->user()->doctor;

        if (! $doctor || $workplace->doctor_id !== $doctor->id) {
            return response()->json(['message' => 'Workplace not found.'], 404);
        }

        $workplace->delete();

        return response()->json(['message' => 'Workplace deleted successfully.']);
    }
}
