<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Company\LoginRequest;
use App\Http\Requests\API\V1\Company\RegisterCompanyRequest;
use App\Http\Requests\API\V1\Company\UpdateCompanyRequest;
use App\Http\Resources\API\V1\Company\CompanyResource;
use App\Http\Resources\API\V1\Company\DashboardStatsResource;
use App\Mail\VerifyEmailMail;
use App\Models\PharmaceuticalCompany;
use App\Models\RepresentativeVisit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    /**
     * Login company owner.
     *
     * Validate credentials, ensure the user is a company owner
     * with an active company, and issue a Sanctum token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = User::where('email', $validated['email'])->with('pharmaceuticalCompany')->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (is_null($user->email_verified_at)) {
            return response()->json(['message' => 'Email not verified. Please check your inbox for the verification code.'], 403);
        }

        if (! $user->hasRole('company_owner') || ! $user->pharmaceuticalCompany) {
            return response()->json(['message' => 'Unauthorized. User is not a company owner.'], 403);
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

        $company = $user->pharmaceuticalCompany->load('owner');

        return response()->json([
            'message' => 'Login successful.',
            'data' => [
                'company' => new CompanyResource($company),
                'role' => $user->getRoleNames()->first(),
                'token' => $user->createToken('company-api-token')->plainTextToken,
            ],
        ]);
    }

    /**
     * Register a new user as company owner with a pharmaceutical company.
     *
     * Creates User and PharmaceuticalCompany in one transaction,
     * assigns the company_owner role, sends a verification email,
     * and returns the company profile. The user must verify their email
     * and then login to receive a Sanctum token.
     */
    public function register(RegisterCompanyRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = DB::transaction(function () use ($request, $validated): array {
            $user = User::create([
                'f_name' => $validated['f_name'],
                'l_name' => $validated['l_name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'phone_number' => $validated['phone_number'],
                'age' => $validated['age'],
                'gender' => $validated['gender'],
                'location' => $validated['location'] ?? null,
            ]);
            $user->assignRole('company_owner');

            $company = PharmaceuticalCompany::create([
                'owner_id' => $user->id,
                'commercial_name' => $validated['commercial_name'],
                'commercial_registration' => $validated['commercial_registration'],
                'address' => $validated['address'],
                'phone' => $validated['phone'],
                'license_number' => $validated['license_number'],
                'license_image' => $request->file('license_image')->store('company_licenses', 'public'),
                'status' => 'pending',
            ]);

            return ['company' => $company->load('owner'), 'user' => $user];
        });

        $verificationToken = (string) random_int(100000, 999999);
        DB::table('email_verification_tokens')->updateOrInsert(
            ['email' => $result['user']->email],
            ['token' => Hash::make($verificationToken), 'created_at' => now()]
        );
        Mail::to($result['user']->email)->queue(new VerifyEmailMail($result['user'], $verificationToken));

        return response()->json([
            'message' => 'Company registration submitted for admin review. Please verify your email before logging in.',
            'data' => [
                'company' => new CompanyResource($result['company']),
            ],
        ], 201);
    }

    /**
     * Show company profile.
     *
     * Return the authenticated owner's company profile and approval status.
     */
    public function profile(Request $request): JsonResponse
    {
        $company = $request->user()->pharmaceuticalCompany?->load('owner');

        if (! $company) {
            return response()->json(['message' => 'Company profile not found.'], 404);
        }

        return response()->json(['data' => new CompanyResource($company)]);
    }

    /**
     * Logout company owner.
     *
     * Revoke the current Sanctum token to log the user out.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Company dashboard.
     *
     * Aggregate reps, assignments, schedules, visits, and adherence rate
     * for the active company dashboard.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $company = $request->user()->pharmaceuticalCompany;

        if (! $company) {
            return response()->json(['message' => 'Company profile not found.'], 404);
        }

        if ($company->status !== 'active') {
            return response()->json(['message' => 'Company must be approved before this action.'], 403);
        }

        $repIds = $company->scientificReps()->pluck('id');
        $totalVisits = RepresentativeVisit::whereIn('rep_id', $repIds)->count();
        $verifiedVisits = RepresentativeVisit::whereIn('rep_id', $repIds)->where('verification_status', true)->count();

        return response()->json([
            'data' => [
                'company' => new CompanyResource($company->load('owner')),
                'stats' => new DashboardStatsResource([
                    'total_reps' => $company->scientificReps()->count(),
                    'total_assignments' => $company->doctorAssignments()->count(),
                    'total_schedules' => $company->scientificReps()->withCount('weeklySchedules')->get()->sum('weekly_schedules_count'),
                    'completed_visits' => $totalVisits,
                    'verified_visits' => $verifiedVisits,
                    'failed_visits' => max(0, $totalVisits - $verifiedVisits),
                    'adherence_rate' => $totalVisits > 0 ? round(($verifiedVisits / $totalVisits) * 100, 2) : 0,
                ]),
            ],
        ]);
    }

    /**
     * Update company profile.
     *
     * Block pending companies from operational changes, then apply the
     * validated profile update and replace the license image when uploaded.
     */
    public function updateProfile(UpdateCompanyRequest $request): JsonResponse
    {
        $company = $request->user()->pharmaceuticalCompany;

        if (! $company) {
            return response()->json(['message' => 'Company profile not found.'], 404);
        }

        if ($company->status !== 'active') {
            return response()->json(['message' => 'Company must be approved before this action.'], 403);
        }

        $validated = $request->validated();
        if ($request->hasFile('license_image')) {
            Storage::disk('public')->delete($company->license_image);
            $validated['license_image'] = $request->file('license_image')->store('company_licenses', 'public');
        }

        $company->update($validated);

        return response()->json([
            'message' => 'Company profile updated successfully.',
            'data' => new CompanyResource($company->fresh()->load('owner')),
        ]);
    }
}
