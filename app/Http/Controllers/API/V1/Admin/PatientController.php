<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Admin\StorePatientRequest;
use App\Http\Requests\API\V1\Admin\UpdatePatientRequest;
use App\Http\Resources\API\V1\Admin\AdminPatientResource;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;

class PatientController extends Controller
{
    public function index(): JsonResponse
    {
        $patients = Patient::with('user')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => AdminPatientResource::collection($patients),
            'meta' => [
                'current_page' => $patients->currentPage(),
                'last_page' => $patients->lastPage(),
                'per_page' => $patients->perPage(),
                'total' => $patients->total(),
            ],
        ]);
    }

    public function show(Patient $patient): JsonResponse
    {
        return response()->json([
            'data' => new AdminPatientResource($patient->load('user')),
        ]);
    }

    public function store(StorePatientRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $patient = Patient::create(['user_id' => $validated['user_id']]);

        return response()->json([
            'message' => 'Patient created successfully.',
            'data' => new AdminPatientResource($patient->load('user')),
        ], 201);
    }

    public function update(UpdatePatientRequest $request, Patient $patient): JsonResponse
    {
        $patient->update($request->validated());

        return response()->json([
            'message' => 'Patient updated successfully.',
            'data' => new AdminPatientResource($patient->fresh()->load('user')),
        ]);
    }

    public function destroy(Patient $patient): JsonResponse
    {
        $patient->user->delete();

        return response()->json(['message' => 'Patient deleted successfully.']);
    }
}
