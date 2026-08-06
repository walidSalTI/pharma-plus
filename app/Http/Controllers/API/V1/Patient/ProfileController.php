<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Patient\UpdateProfileRequest;
use App\Http\Resources\API\V1\Patient\PatientProfileResource;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    /**
     * Display the authenticated patient's profile.
     *
     * Fetches and returns the patient's personal information
     * from the `users` table along with patient-specific fields
     * (`blood_type`, `latitude`, `longitude`) from the `patients` table.
     *
     * FR-P-1.3: Demographics Upkeep — view personal metrics.
     * FR-P-2.4: Profile Data Control — read clinical profile ledger.
     */
    public function show(): JsonResponse
    {
        $user = request()->user()->load('patient');

        return response()->json([
            'data' => new PatientProfileResource($user),
        ]);
    }

    /**
     * Update the authenticated patient's profile.
     *
     * Updates fields on both the `users` table (f_name, l_name, email,
     * phone_number, age, gender, location) and the `patients` table
     * (blood_type, latitude, longitude). Only provided fields are updated.
     *
     * Since global `Model::unguard()` is enabled, all writes use
     * `$request->validated()` exclusively for security.
     *
     * FR-P-1.3: Demographics Upkeep — update personal metrics.
     * FR-P-2.4: Profile Data Control — append/update clinical profile entries.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $patient = $user->patient;

        $userFields = ['f_name', 'l_name', 'email', 'phone_number', 'age', 'gender', 'location'];
        $userData = array_intersect_key($validated, array_flip($userFields));
        if ($userData !== []) {
            $user->update($userData);
        }

        $patientFields = ['blood_type', 'latitude', 'longitude'];
        $patientData = array_intersect_key($validated, array_flip($patientFields));
        if ($patientData !== []) {
            $patient->update($patientData);
        }

        $user->load('patient');

        return response()->json([
            'message' => 'Profile updated successfully.',
            'data' => new PatientProfileResource($user),
        ]);
    }
}
