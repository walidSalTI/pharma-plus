<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Admin\StoreCompanyRequest;
use App\Http\Requests\API\V1\Admin\UpdateCompanyRequest;
use App\Http\Resources\API\V1\Admin\AdminCompanyResource;
use App\Models\PharmaceuticalCompany;
use Illuminate\Http\JsonResponse;

class CompanyController extends Controller
{
    public function index(): JsonResponse
    {
        $companies = PharmaceuticalCompany::with('owner')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => AdminCompanyResource::collection($companies),
            'meta' => [
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
                'per_page' => $companies->perPage(),
                'total' => $companies->total(),
            ],
        ]);
    }

    public function show(PharmaceuticalCompany $pharmaceuticalCompany): JsonResponse
    {
        return response()->json([
            'data' => new AdminCompanyResource($pharmaceuticalCompany->load('owner', 'scientificReps.user')),
        ]);
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $company = PharmaceuticalCompany::create([
            'owner_id' => $validated['owner_id'],
            'commercial_name' => $validated['commercial_name'],
            'commercial_registration' => $validated['commercial_registration'],
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'license_number' => $validated['license_number'] ?? null,
            'status' => $validated['status'] ?? 'pending',
        ]);

        return response()->json([
            'message' => 'Company created successfully.',
            'data' => new AdminCompanyResource($company->load('owner')),
        ], 201);
    }

    public function update(UpdateCompanyRequest $request, PharmaceuticalCompany $pharmaceuticalCompany): JsonResponse
    {
        $pharmaceuticalCompany->update($request->validated());

        return response()->json([
            'message' => 'Company updated successfully.',
            'data' => new AdminCompanyResource($pharmaceuticalCompany->fresh()->load('owner')),
        ]);
    }

    public function destroy(PharmaceuticalCompany $pharmaceuticalCompany): JsonResponse
    {
        $pharmaceuticalCompany->owner->delete();

        return response()->json(['message' => 'Company deleted successfully.']);
    }
}
