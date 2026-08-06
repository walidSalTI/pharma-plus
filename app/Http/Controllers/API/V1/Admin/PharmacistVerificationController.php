<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Admin\VerifyPharmacistRequest;
use App\Http\Resources\API\V1\Admin\PharmacistVerificationResource;
use App\Models\Pharmacist;
use Illuminate\Http\JsonResponse;

class PharmacistVerificationController extends Controller
{
    public function pending(): JsonResponse
    {
        $pharmacists = Pharmacist::with('user')
            ->where('verification_status', 'pending')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => PharmacistVerificationResource::collection($pharmacists),
            'meta' => [
                'current_page' => $pharmacists->currentPage(),
                'last_page' => $pharmacists->lastPage(),
                'per_page' => $pharmacists->perPage(),
                'total' => $pharmacists->total(),
            ],
        ]);
    }

    public function verify(VerifyPharmacistRequest $request, Pharmacist $pharmacist): JsonResponse
    {
        $validated = $request->validated();

        $pharmacist->update(['verification_status' => $validated['status']]);

        $message = $validated['status'] === 'approved'
            ? 'Pharmacist verified successfully.'
            : 'Pharmacist rejected.';

        return response()->json(['message' => $message]);
    }
}
