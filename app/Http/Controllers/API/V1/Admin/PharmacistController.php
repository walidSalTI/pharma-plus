<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Admin\StorePharmacistRequest;
use App\Http\Requests\API\V1\Admin\UpdatePharmacistRequest;
use App\Http\Resources\API\V1\Admin\AdminPharmacistResource;
use App\Models\Pharmacist;
use Illuminate\Http\JsonResponse;

class PharmacistController extends Controller
{
    public function index(): JsonResponse
    {
        $pharmacists = Pharmacist::with('user')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => AdminPharmacistResource::collection($pharmacists),
            'meta' => [
                'current_page' => $pharmacists->currentPage(),
                'last_page' => $pharmacists->lastPage(),
                'per_page' => $pharmacists->perPage(),
                'total' => $pharmacists->total(),
            ],
        ]);
    }

    public function show(Pharmacist $pharmacist): JsonResponse
    {
        return response()->json([
            'data' => new AdminPharmacistResource($pharmacist->load('user.pharmacies')),
        ]);
    }

    public function store(StorePharmacistRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $pharmacist = Pharmacist::create([
            'user_id' => $validated['user_id'],
            'verification_status' => $validated['verification_status'] ?? 'unverified',
        ]);

        return response()->json([
            'message' => 'Pharmacist created successfully.',
            'data' => new AdminPharmacistResource($pharmacist->load('user')),
        ], 201);
    }

    public function update(UpdatePharmacistRequest $request, Pharmacist $pharmacist): JsonResponse
    {
        $pharmacist->update($request->validated());

        return response()->json([
            'message' => 'Pharmacist updated successfully.',
            'data' => new AdminPharmacistResource($pharmacist->fresh()->load('user')),
        ]);
    }

    public function destroy(Pharmacist $pharmacist): JsonResponse
    {
        $pharmacist->user->delete();

        return response()->json(['message' => 'Pharmacist deleted successfully.']);
    }
}
