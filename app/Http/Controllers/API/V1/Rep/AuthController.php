<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Rep;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Rep\LoginRepRequest;
use App\Http\Resources\API\V1\Rep\RepDashboardResource;
use App\Models\User;
use App\Models\WeeklySchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Login representative.
     *
     * Validate email and password, ensure the user has the scientific_rep role,
     * and issue a Sanctum token for field operations.
     */
    public function login(LoginRepRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = User::where('email', $validated['email'])->with('scientificRep')->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (is_null($user->email_verified_at)) {
            return response()->json(['message' => 'Email not verified. Please check your inbox for the verification code.'], 403);
        }

        if (! $user->hasRole('scientific_rep') || ! $user->scientificRep) {
            return response()->json(['message' => 'Unauthorized. User is not a representative.'], 403);
        }

        if ($user->google2fa_enabled) {
            $payload = ['sub' => $user->id, 'exp' => now()->addMinutes(5)->timestamp];
            $twoFactorToken = Crypt::encrypt($payload);

            return response()->json([
                'message' => 'Two-factor authentication required.',
                'two_factor' => true,
                'two_factor_token' => $twoFactorToken,
            ]);
        }

        return response()->json([
            'message' => 'Login successful.',
            'data' => [
                'token' => $user->createToken('rep-api-token')->plainTextToken,
                'rep' => [
                    'id' => $user->scientificRep->id,
                    'company_id' => $user->scientificRep->company_id,
                    'f_name' => $user->f_name,
                    'l_name' => $user->l_name,
                    'email' => $user->email,
                    'role' => $user->getRoleNames()->first(),
                ],
            ],
        ]);
    }

    /**
     * Logout representative.
     *
     * Revoke only the current Sanctum token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Show representative dashboard.
     *
     * Load today's schedules, group them by status, and calculate a rolling
     * weekly status overview for the authenticated representative.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $rep = $request->user()->scientificRep;

        if (! $rep) {
            return response()->json(['message' => 'Representative profile not found.'], 404);
        }

        $todaySchedules = WeeklySchedule::with(['doctor.user', 'doctor.doctorWorkplaces'])
            ->where('rep_id', $rep->id)
            ->whereDate('scheduled_at', today())
            ->orderBy('scheduled_at')
            ->get();

        $weeklyOverview = WeeklySchedule::where('rep_id', $rep->id)
            ->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'data' => new RepDashboardResource([
                'rep' => [
                    'id' => $rep->id,
                    'company_id' => $rep->company_id,
                    'name' => trim($request->user()->f_name.' '.$request->user()->l_name),
                    'email' => $request->user()->email,
                ],
                'today_schedules' => $todaySchedules,
                'today_by_status' => $todaySchedules->groupBy('status')->map->count(),
                'weekly_overview' => [
                    'upcoming' => (int) ($weeklyOverview['upcoming'] ?? 0),
                    'completed' => (int) ($weeklyOverview['completed'] ?? 0),
                    'cancelled' => (int) ($weeklyOverview['cancelled'] ?? 0),
                ],
            ]),
        ]);
    }
}
