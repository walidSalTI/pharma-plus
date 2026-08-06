<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Admin\AdminDashboardResource;
use App\Models\Doctor;
use App\Models\MedicationProposal;
use App\Models\PharmaceuticalCompany;
use App\Models\Pharmacist;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $totalUsers = User::count();
        $pendingDoctors = Doctor::whereNull('syndicate_card_image')->orWhere('syndicate_card_image', '')->count();
        $pendingPharmacists = Pharmacist::where('verification_status', 'pending')->count();
        $pendingCompanies = PharmaceuticalCompany::where('status', 'pending')->count();
        $pendingProposals = MedicationProposal::where('status', 'pending')->count();

        return response()->json([
            'data' => new AdminDashboardResource([
                'total_users' => $totalUsers,
                'pending_doctors' => $pendingDoctors,
                'pending_pharmacists' => $pendingPharmacists,
                'pending_companies' => $pendingCompanies,
                'pending_proposals' => $pendingProposals,
            ]),
        ]);
    }
}
