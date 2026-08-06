<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Doctor\DoctorQrResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;

class QrController extends Controller
{
    /**
     * Get the doctor's secure seed for offline TOTP generation.
     *
     * Returns the static secret key once so the frontend can store it
     * in secure storage and generate 6-digit TOTP codes locally using
     * the standard TOTP algorithm (HMAC-SHA1, 30-second window).
     *
     * The secret key is persisted in the database and generated only
     * once on first request. Subsequent calls return the same key.
     *
     * The rep's check-in flow verifies the scanned code via
     * Google2FA::verifyKey() using this same secret key.
     */
    public function getSecretKey(Request $request): JsonResponse
    {
        $doctor = $request->user()->doctor;

        if (! $doctor) {
            return response()->json(['message' => 'Doctor profile not found.'], 404);
        }

        // Generate the secret key once on first request and persist it
        if (! $doctor->doctor_secret_key) {
            $google2fa = new Google2FA;
            $doctor->update(['doctor_secret_key' => $google2fa->generateSecretKey()]);
        }

        // Return the static secret key — frontend stores this securely
        // and derives TOTP codes locally, no need to hit this endpoint again
        return response()->json([
            'data' => new DoctorQrResource([
                'doctor_id' => $doctor->id,
                'secret_key' => $doctor->doctor_secret_key,
            ]),
        ]);
    }
}
