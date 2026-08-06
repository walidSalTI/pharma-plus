<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Admin\VerifyDoctorRequest;
use App\Http\Resources\API\V1\Admin\DoctorVerificationResource;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;

class DoctorVerificationController extends Controller
{
    public function pending(): JsonResponse
    {
        $doctors = Doctor::with('user')
            ->where('verification_status', 'pending')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => DoctorVerificationResource::collection($doctors),
            'meta' => [
                'current_page' => $doctors->currentPage(),
                'last_page' => $doctors->lastPage(),
                'per_page' => $doctors->perPage(),
                'total' => $doctors->total(),
            ],
        ]);
    }

    public function verify(VerifyDoctorRequest $request, Doctor $doctor): JsonResponse
    {
        $validated = $request->validated();

        $doctor->update(['verification_status' => $validated['status']]);

        $message = $validated['status'] === 'approved'
            ? 'Doctor verified successfully.'
            : 'Doctor rejected.';

        return response()->json(['message' => $message]);
    }
}
