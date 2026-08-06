<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Admin\StoreDoctorRequest;
use App\Http\Requests\API\V1\Admin\UpdateDoctorRequest;
use App\Http\Resources\API\V1\Admin\AdminDoctorResource;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DoctorController extends Controller
{
    public function index(): JsonResponse
    {
        $doctors = Doctor::with('user')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => AdminDoctorResource::collection($doctors),
            'meta' => [
                'current_page' => $doctors->currentPage(),
                'last_page' => $doctors->lastPage(),
                'per_page' => $doctors->perPage(),
                'total' => $doctors->total(),
            ],
        ]);
    }

    public function show(Doctor $doctor): JsonResponse
    {
        return response()->json([
            'data' => new AdminDoctorResource($doctor->load('user', 'doctorWorkplaces')),
        ]);
    }

    public function store(StoreDoctorRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $doctor = Doctor::create([
            'user_id' => $validated['user_id'],
            'specialization' => $validated['specialization'],
            'verification_status' => $validated['verification_status'] ?? 'unverified',
        ]);

        return response()->json([
            'message' => 'Doctor created successfully.',
            'data' => new AdminDoctorResource($doctor->load('user')),
        ], 201);
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor): JsonResponse
    {
        $doctor->update($request->validated());

        return response()->json([
            'message' => 'Doctor updated successfully.',
            'data' => new AdminDoctorResource($doctor->fresh()->load('user')),
        ]);
    }

    public function destroy(Doctor $doctor): JsonResponse
    {
        $doctor->user->delete();

        return response()->json(['message' => 'Doctor deleted successfully.']);
    }

    public function restore(string $id): JsonResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        return response()->json(['message' => 'Doctor restored successfully.']);
    }
}
