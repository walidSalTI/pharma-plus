<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Company\VerifyCompanyRequest;
use App\Http\Resources\API\V1\Company\CompanyResource;
use App\Models\PharmaceuticalCompany;
use Illuminate\Http\JsonResponse;

class AdminVerificationController extends Controller
{
    /**
     * List pending companies.
     *
     * Return company registrations waiting for administrator approval.
     */
    public function pending(): JsonResponse
    {
        $companies = PharmaceuticalCompany::with('owner')
            ->where('status', 'pending')
            ->latest()
            ->paginate(25);

        return response()->json([
            'data' => CompanyResource::collection($companies),
            'meta' => [
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
                'per_page' => $companies->perPage(),
                'total' => $companies->total(),
            ],
        ]);
    }

    /**
     * Verify company.
     *
     * Admin sets active, rejected, or suspended status. Rejection reason
     * is accepted for audit visibility but not persisted in the current schema.
     */
    public function verify(VerifyCompanyRequest $request, PharmaceuticalCompany $company): JsonResponse
    {
        $company->update(['status' => $request->validated()['status']]);

        return response()->json([
            'message' => 'Company verification status updated.',
            'data' => new CompanyResource($company->fresh()->load('owner')),
        ]);
    }
}
