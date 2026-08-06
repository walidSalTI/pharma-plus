<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Specialist;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Specialist\LoginSpecialistRequest;
use App\Http\Resources\API\V1\Specialist\SpecialistDashboardResource;
use App\Models\MedicationProposal;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginSpecialistRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = User::where('email', $validated['email'])->with('specialist')->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (is_null($user->email_verified_at)) {
            return response()->json(['message' => 'Email not verified. Please check your inbox for the verification code.'], 403);
        }

        if (! $user->hasRole('specialist') || ! $user->specialist) {
            return response()->json(['message' => 'Unauthorized. User is not a specialist.'], 403);
        }

        return response()->json([
            'message' => 'Login successful.',
            'data' => [
                'token' => $user->createToken('specialist-api-token')->plainTextToken,
                'specialist' => [
                    'id' => $user->specialist->id,
                    'specialization' => $user->specialist->specialization,
                    'f_name' => $user->f_name,
                    'l_name' => $user->l_name,
                    'email' => $user->email,
                    'role' => $user->getRoleNames()->first(),
                ],
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $specialist = $request->user()->specialist;

        if (! $specialist) {
            return response()->json(['message' => 'Specialist profile not found.'], 404);
        }

        $pendingCount = MedicationProposal::whereNull('specialist_id')
            ->where('status', 'pending')
            ->count();

        $recentProposals = MedicationProposal::with('pharmacist.user')
            ->where('status', 'pending')
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'data' => new SpecialistDashboardResource([
                'specialist' => [
                    'id' => $specialist->id,
                    'specialization' => $specialist->specialization,
                    'name' => trim($request->user()->f_name.' '.$request->user()->l_name),
                    'email' => $request->user()->email,
                ],
                'pending_proposals_count' => $pendingCount,
                'recent_proposals' => $recentProposals,
            ]),
        ]);
    }
}
