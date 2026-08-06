<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Admin\StoreSpecialistRequest;
use App\Http\Requests\API\V1\Admin\UpdateSpecialistRequest;
use App\Http\Resources\API\V1\Admin\AdminSpecialistResource;
use App\Models\Specialist;
use Illuminate\Http\JsonResponse;

class SpecialistController extends Controller
{
    public function index(): JsonResponse
    {
        $specialists = Specialist::with('user')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => AdminSpecialistResource::collection($specialists),
            'meta' => [
                'current_page' => $specialists->currentPage(),
                'last_page' => $specialists->lastPage(),
                'per_page' => $specialists->perPage(),
                'total' => $specialists->total(),
            ],
        ]);
    }

    public function show(Specialist $specialist): JsonResponse
    {
        return response()->json([
            'data' => new AdminSpecialistResource($specialist->load('user')),
        ]);
    }

    public function store(StoreSpecialistRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $specialist = Specialist::create(['user_id' => $validated['user_id']]);

        return response()->json([
            'message' => 'Specialist created successfully.',
            'data' => new AdminSpecialistResource($specialist->load('user')),
        ], 201);
    }

    public function update(UpdateSpecialistRequest $request, Specialist $specialist): JsonResponse
    {
        $specialist->update($request->validated());

        return response()->json([
            'message' => 'Specialist updated successfully.',
            'data' => new AdminSpecialistResource($specialist->fresh()->load('user')),
        ]);
    }

    public function destroy(Specialist $specialist): JsonResponse
    {
        $specialist->user->delete();

        return response()->json(['message' => 'Specialist deleted successfully.']);
    }
}
