<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Pharmacy\LoginRequest;
use App\Http\Requests\API\V1\Pharmacy\RegisterRequest;
use App\Mail\VerifyEmailMail;
use App\Models\Pharmacist;
use App\Models\Pharmacy;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Pharmacist Authentication & Identity Verification (FR-PH-1).
 *
 * Handles registration, login, logout, the workspace dashboard,
 * and the self-verification flow (uploading syndicate card).
 *
 * New flow:
 * - Register without syndicate_card → unverified
 * - Pharmacist uploads syndicate_card later → pending
 * - Admin approves/rejects → approved / rejected
 * - Only verified pharmacists can create a pharmacy
 * - Unverified pharmacists can work in other pharmacies as staff
 */
class AuthController extends Controller
{
    /**
     * Register a new pharmacist account.
     *
     * Creates a User and Pharmacist record. The syndicate_card is
     * optional at registration. The pharmacist starts as 'unverified'
     * and can later submit their syndicate card for admin verification.
     * The pharmacist must verify their email and then login to receive a Sanctum token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $syndicateCardPath = null;
        if ($request->hasFile('syndicate_card')) {
            $syndicateCardPath = $request->file('syndicate_card')->store('syndicate_cards', 'public');
        }

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

        $user->assignRole('pharmacist');

        $pharmacist = Pharmacist::create([
            'user_id' => $user->id,
            'syndicate_card' => $syndicateCardPath,
            'verification_status' => $syndicateCardPath ? 'pending' : 'unverified',
        ]);

        $verificationToken = (string) random_int(100000, 999999);
        DB::table('email_verification_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($verificationToken), 'created_at' => now()]
        );
        Mail::to($user->email)->queue(new VerifyEmailMail($user, $verificationToken));

        return response()->json([
            'message' => 'Pharmacist registered successfully. Please verify your email before logging in.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'f_name' => $user->f_name,
                    'l_name' => $user->l_name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                ],
                'pharmacist' => [
                    'id' => $pharmacist->id,
                    'verification_status' => $pharmacist->verification_status,
                ],
            ],
        ], 201);
    }

    /**
     * Login an existing pharmacist.
     *
     * Validates email and password, ensures the user has the 'pharmacist'
     * role, then issues a new Sanctum token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (! $user->hasRole('pharmacist')) {
            return response()->json(['message' => 'Unauthorized. User is not a pharmacist.'], 403);
        }

        if (is_null($user->email_verified_at)) {
            return response()->json(['message' => 'Email not verified. Please check your inbox for the verification code.'], 403);
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

        $token = $user->createToken('pharmacist-api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'f_name' => $user->f_name,
                    'l_name' => $user->l_name,
                    'email' => $user->email,
                ],
            ],
        ]);
    }

    /**
     * Logout the current pharmacist.
     *
     * Revokes the current Sanctum token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Submit syndicate card for verification (FR-PH-1.2).
     *
     * Allows an unverified pharmacist to upload their syndicate card.
     * Sets verification_status to 'pending' for admin review.
     */
    public function submitVerification(Request $request): JsonResponse
    {
        $pharmacist = $request->user()->pharmacist;

        if (! $pharmacist) {
            return response()->json(['message' => 'Pharmacist profile not found.'], 404);
        }

        if ($pharmacist->verification_status === 'approved') {
            return response()->json(['message' => 'Account is already verified.'], 422);
        }

        if ($pharmacist->verification_status === 'pending') {
            return response()->json(['message' => 'Verification is already pending review.'], 422);
        }

        $request->validate([
            'syndicate_card' => ['required', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        if ($pharmacist->syndicate_card) {
            Storage::disk('public')->delete($pharmacist->syndicate_card);
        }

        $path = $request->file('syndicate_card')->store('syndicate_cards', 'public');

        $pharmacist->update([
            'syndicate_card' => $path,
            'verification_status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Syndicate card submitted for verification.',
            'data' => [
                'verification_status' => $pharmacist->verification_status,
            ],
        ]);
    }

    /**
     * Get the current pharmacist's verification status.
     */
    public function verificationStatus(Request $request): JsonResponse
    {
        $pharmacist = $request->user()->pharmacist;

        if (! $pharmacist) {
            return response()->json(['message' => 'Pharmacist profile not found.'], 404);
        }

        return response()->json([
            'data' => [
                'verification_status' => $pharmacist->verification_status,
                'syndicate_card' => $pharmacist->syndicate_card
                    ? asset('storage/'.$pharmacist->syndicate_card)
                    : null,
            ],
        ]);
    }

    /**
     * Show the pharmacist workspace dashboard (FR-PH-1.3).
     *
     * Returns the pharmacist's sidebar info (name, verification status)
     * and a list of all pharmacies they are associated with (owned or staff),
     * with each pharmacy flagged as is_owner or not.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $pharmacist = $request->user()->pharmacist;

        if (! $pharmacist) {
            return response()->json(['message' => 'Pharmacist profile not found.'], 404);
        }

        $user = $request->user();

        $staffPharmacies = $pharmacist->staffPharmacies()
            ->get()
            ->toBase() // تحويل إلى Base Collection لتجنب مشاكل الـ Eloquent merge
            ->map(fn (Pharmacy $pharmacy) => [
                'id' => $pharmacy->id,
                'name' => $pharmacy->name,
                'address' => $pharmacy->address,
                'is_owner' => false,
                'permissions' => [
                    'pharmacy_manage' => (bool) $pharmacy->pivot->pharmacy_manage,
                    'inventory_manage' => (bool) $pharmacy->pivot->inventory_manage,
                    'operating_hours_manage' => (bool) $pharmacy->pivot->operating_hours_manage,
                    'orders_process' => (bool) $pharmacy->pivot->orders_process,
                    'orders_view_own' => (bool) $pharmacy->pivot->orders_view_own,
                ],
                'created_at' => $pharmacy->created_at,
            ]);

        $ownedPharmacies = $pharmacist->pharmacies()
            ->get()
            ->toBase() // تحويل إلى Base Collection
            ->map(fn (Pharmacy $pharmacy) => [
                'id' => $pharmacy->id,
                'name' => $pharmacy->name,
                'address' => $pharmacy->address,
                'is_owner' => true,
                'created_at' => $pharmacy->created_at,
            ]);

        // الآن الـ merge سيعمل بسلاسة لأن الطرفين عبارة عن مصفوفات داخل Base Collection
        $allPharmacies = $ownedPharmacies->merge($staffPharmacies)
            ->unique('id')
            ->sortBy('name')
            ->values();

        return response()->json([
            'data' => [
                'user' => [
                    'f_name' => $user->f_name,
                    'l_name' => $user->l_name,
                ],
                'pharmacist' => [
                    'id' => $pharmacist->id,
                    'verification_status' => $pharmacist->verification_status,
                    'can_create_pharmacy' => $pharmacist->isVerified(),
                ],
                'pharmacies' => $allPharmacies,
            ],
        ]);
    }
}
