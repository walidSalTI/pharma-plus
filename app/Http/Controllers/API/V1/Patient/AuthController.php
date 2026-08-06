<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Patient\LoginRequest;
use App\Http\Requests\API\V1\Patient\RegisterRequest;
use App\Mail\VerifyEmailMail;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

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

        $user->assignRole('patient');

        $patient = Patient::create([
            'user_id' => $user->id,
            'blood_type' => $validated['blood_type'] ?? null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
        ]);

        $verificationToken = (string) random_int(100000, 999999);
        DB::table('email_verification_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($verificationToken), 'created_at' => now()]
        );
        Mail::to($user->email)->queue(new VerifyEmailMail($user, $verificationToken));

        return response()->json([
            'message' => 'Patient registered successfully. Please verify your email before logging in.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'f_name' => $user->f_name,
                    'l_name' => $user->l_name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                ],
                'patient' => [
                    'id' => $patient->id,
                    'blood_type' => $patient->blood_type,
                ],
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (! $user->hasRole('patient')) {
            return response()->json(['message' => 'Unauthorized. User is not a patient.'], 403);
        }

        if (is_null($user->email_verified_at)) {
            return response()->json(['message' => 'Email not verified. Please check your inbox for the verification code.'], 403);
        }

        $token = $user->createToken('patient-api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
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
}
