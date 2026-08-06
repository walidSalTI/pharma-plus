<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Doctor\LoginDoctorRequest;
use App\Http\Requests\API\V1\Doctor\RegisterDoctorRequest;
use App\Http\Requests\API\V1\Doctor\UpdateDoctorProfileRequest;
use App\Http\Resources\API\V1\Doctor\DoctorProfileResource;
use App\Mail\VerifyEmailMail;
use App\Models\Doctor;
use App\Models\DoctorWorkplace;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PragmaRX\Google2FA\Google2FA;

class AuthController extends Controller
{
    /**
     * Register doctor.
     *
     * Validate user, doctor, and optional workplaces payload.
     * Store the syndicate image if uploaded.
     * Create User, Doctor, and DoctorWorkplace rows inside one database transaction.
     * Assign the doctor role, send a verification email, and return the doctor profile.
     * The doctor must verify their email and then login to receive a Sanctum token.
     */
    public function register(RegisterDoctorRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $doctor = DB::transaction(function () use ($request, $validated): Doctor {
            $syndicateCardPath = $request->hasFile('syndicate_card_image')
                ? $request->file('syndicate_card_image')->store('doctor_syndicate_cards', 'public')
                : null;

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
            $user->assignRole('doctor');

            $initialStatus = $syndicateCardPath ? 'pending' : 'unverified';

            $doctor = Doctor::create([
                'user_id' => $user->id,
                'specialization' => $validated['specialization'],
                'syndicate_card_image' => $syndicateCardPath,
                'doctor_secret_key' => (new Google2FA)->generateSecretKey(),
                'verification_status' => $initialStatus,
            ]);

            foreach ($validated['workplaces'] ?? [] as $workplace) {
                DoctorWorkplace::create([
                    'doctor_id' => $doctor->id,
                    'place_name' => $workplace['place_name'],
                    'place_type' => $workplace['place_type'],
                    'latitude' => $workplace['latitude'],
                    'longitude' => $workplace['longitude'],
                    'radius_meters' => $workplace['radius_meters'] ?? 50,
                ]);
            }

            return $doctor->load(['user', 'doctorWorkplaces']);
        });

        $verificationToken = (string) random_int(100000, 999999);
        DB::table('email_verification_tokens')->updateOrInsert(
            ['email' => $doctor->user->email],
            ['token' => Hash::make($verificationToken), 'created_at' => now()]
        );
        Mail::to($doctor->user->email)->queue(new VerifyEmailMail($doctor->user, $verificationToken));

        return response()->json([
            'message' => 'Doctor registered successfully. Please verify your email before logging in.',
            'data' => [
                'doctor' => new DoctorProfileResource($doctor),
            ],
        ], 201);
    }

    /**
     * Login doctor.
     *
     * Validate credentials, ensure the user has the doctor role,
     * issue a Sanctum token, and return the doctor profile.
     */
    public function login(LoginDoctorRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = User::where('email', $validated['email'])->with('doctor')->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (is_null($user->email_verified_at)) {
            return response()->json(['message' => 'Email not verified. Please check your inbox for the verification code.'], 403);
        }

        if (! $user->hasRole('doctor') || ! $user->doctor) {
            return response()->json(['message' => 'Unauthorized. User is not a doctor.'], 403);
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
                'doctor' => new DoctorProfileResource($user->doctor->load(['user', 'doctorWorkplaces'])),
                'role' => $user->getRoleNames()->first(),
                'token' => $user->createToken('doctor-api-token')->plainTextToken,
            ],
        ]);
    }

    /**
     * Logout doctor.
     *
     * Revoke only the current Sanctum token so other devices remain signed in.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Show doctor profile.
     *
     * Load the authenticated doctor's user and workplaces and return
     * the canonical doctor profile resource.
     */
    public function profile(Request $request): JsonResponse
    {
        $doctor = $request->user()->doctor?->load(['user', 'doctorWorkplaces']);

        if (! $doctor) {
            return response()->json(['message' => 'Doctor profile not found.'], 404);
        }

        return response()->json(['data' => new DoctorProfileResource($doctor)]);
    }

    /**
     * Update doctor profile.
     *
     * Use the validated partial payload to update user identity fields
     * and doctor-specific fields. Replace the syndicate card image when uploaded.
     */
    public function updateProfile(UpdateDoctorProfileRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $doctor = $user->doctor;

        if (! $doctor) {
            return response()->json(['message' => 'Doctor profile not found.'], 404);
        }

        DB::transaction(function () use ($request, $validated, $user, $doctor): void {
            $user->update(array_intersect_key($validated, array_flip([
                'f_name', 'l_name', 'email', 'phone_number', 'age', 'gender', 'location',
            ])));

            $doctorData = array_intersect_key($validated, array_flip(['specialization']));
            if ($request->hasFile('syndicate_card_image')) {
                if ($doctor->syndicate_card_image) {
                    Storage::disk('public')->delete($doctor->syndicate_card_image);
                }
                $doctorData['syndicate_card_image'] = $request->file('syndicate_card_image')->store('doctor_syndicate_cards', 'public');
            }

            if ($doctorData !== []) {
                $doctor->update($doctorData);
            }
        });

        return response()->json([
            'message' => 'Doctor profile updated successfully.',
            'data' => new DoctorProfileResource($doctor->fresh()->load(['user', 'doctorWorkplaces'])),
        ]);
    }

    /**
     * Submit syndicate card for verification.
     *
     * Allows an unverified doctor to upload their syndicate card.
     * Sets verification_status to 'pending' for admin review.
     */
    public function submitVerification(Request $request): JsonResponse
    {
        $doctor = $request->user()->doctor;

        if (! $doctor) {
            return response()->json(['message' => 'Doctor profile not found.'], 404);
        }

        if ($doctor->verification_status === 'approved') {
            return response()->json(['message' => 'Account is already verified.'], 422);
        }

        if ($doctor->verification_status === 'pending') {
            return response()->json(['message' => 'Verification is already pending review.'], 422);
        }

        $request->validate([
            'syndicate_card_image' => ['required', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        if ($doctor->syndicate_card_image) {
            Storage::disk('public')->delete($doctor->syndicate_card_image);
        }

        $path = $request->file('syndicate_card_image')->store('doctor_syndicate_cards', 'public');

        $doctor->update([
            'syndicate_card_image' => $path,
            'verification_status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Syndicate card submitted for verification.',
            'data' => [
                'verification_status' => $doctor->verification_status,
            ],
        ]);
    }
}
