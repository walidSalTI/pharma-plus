<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\TwoFactor\ConfirmTwoFactorRequest;
use App\Http\Requests\API\V1\TwoFactor\DisableTwoFactorRequest;
use App\Http\Requests\API\V1\TwoFactor\VerifyTwoFactorRequest;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    /**
     * Get two-factor authentication status.
     *
     * Return whether 2FA is currently enabled for the authenticated user.
     */
    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'enabled' => $request->user()->google2fa_enabled,
            ],
        ]);
    }

    /**
     * Enable two-factor authentication.
     *
     * Generate a new TOTP secret, store it temporarily on the user,
     * and return the secret key along with a QR code URL for the
     * authenticator app. The secret is NOT persisted until confirm.
     */
    public function enable(Request $request): JsonResponse
    {
        $user = $request->user();
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $request->user()->update(['google2fa_secret' => $secret]);

        $qrCodeUrl = $google2fa->getQRCodeUrl(

            config('app.name'),

            $user->email,

            $secret,

        );

        return response()->json([
            'message' => 'Scan the QR code with your authenticator app, then confirm with a valid code.',
            'data' => [
                'secret' => $secret,
                'qr_code_url' => $qrCodeUrl,
            ],
        ]);
    }

    /**
     * Confirm two-factor authentication setup.
     *
     * Verify the 6-digit code from the authenticator app against the
     * stored secret. On success, generate 8 recovery codes, hash them,
     * and persist everything. Enable 2FA on the user account.
     */
    public function confirm(ConfirmTwoFactorRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        if ($user->google2fa_enabled) {
            return response()->json(['message' => 'Two-factor authentication is already enabled.'], 422);
        }

        $google2fa = new Google2FA;
        $valid = $google2fa->verifyKey($user->google2fa_secret, $validated['code']);

        if (! $valid) {
            return response()->json(['message' => 'Invalid two-factor code.'], 422);
        }

        $recoveryCodes = [];
        $hashedRecoveryCodes = [];

        for ($i = 0; $i < 8; $i++) {
            $code = bin2hex(random_bytes(5));
            $recoveryCodes[] = $code;
            $hashedRecoveryCodes[] = Hash::make($code);
        }

        $user->update([
            'google2fa_enabled' => true,
            'google2fa_recovery_codes' => $hashedRecoveryCodes,
        ]);

        return response()->json([
            'message' => 'Two-factor authentication enabled successfully. Save your recovery codes — they will not be shown again.',
            'data' => [
                'recovery_codes' => $recoveryCodes,
            ],
        ]);
    }

    /**
     * Disable two-factor authentication.
     *
     * Require the user's password and a valid 6-digit TOTP code.
     * On success, clear all 2FA fields and disable the feature.
     */
    public function disable(DisableTwoFactorRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        if (! $user->google2fa_enabled) {
            return response()->json(['message' => 'Two-factor authentication is not enabled.'], 422);
        }

        if (! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid password.'], 422);
        }

        $google2fa = new Google2FA;
        $valid = $google2fa->verifyKey($user->google2fa_secret, $validated['code']);

        if (! $valid) {
            return response()->json(['message' => 'Invalid two-factor code.'], 422);
        }

        $user->update([
            'google2fa_enabled' => false,
            'google2fa_secret' => null,
            'google2fa_recovery_codes' => null,
        ]);

        return response()->json([
            'message' => 'Two-factor authentication disabled successfully.',
        ]);
    }

    /**
     * Get recovery codes information.
     *
     * Recovery codes are only shown once upon enable/confirm.
     * This endpoint returns a message informing the user of that.
     */
    public function recoveryCodes(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Recovery codes are only displayed once when two-factor authentication is enabled. If you have lost your recovery codes, disable and re-enable two-factor authentication to generate new ones.',
        ]);
    }

    /**
     * Verify two-factor authentication (step 2 of login).
     *
     * Accept an encrypted two_factor_token (issued during login when
     * 2FA is enabled) and either a 6-digit code or a recovery code.
     * On success, issue a Sanctum token to complete the login.
     */
    public function verify(VerifyTwoFactorRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $payload = Crypt::decrypt($validated['two_factor_token']);
        } catch (Exception) {
            return response()->json(['message' => 'Invalid or expired two-factor token.'], 422);
        }

        if (! isset($payload['sub']) || ! isset($payload['exp']) || $payload['exp'] < now()->timestamp) {
            return response()->json(['message' => 'Invalid or expired two-factor token.'], 422);
        }

        $user = User::find($payload['sub']);

        if (! $user || ! $user->google2fa_enabled) {
            return response()->json(['message' => 'Invalid two-factor token.'], 422);
        }

        if (isset($validated['recovery_code'])) {
            $recoveryCodes = $user->google2fa_recovery_codes ?? [];
            $matched = false;

            foreach ($recoveryCodes as $index => $hashedCode) {
                if (Hash::check($validated['recovery_code'], $hashedCode)) {
                    unset($recoveryCodes[$index]);
                    $user->update(['google2fa_recovery_codes' => array_values($recoveryCodes)]);
                    $matched = true;
                    break;
                }
            }

            if (! $matched) {
                return response()->json(['message' => 'Invalid recovery code.'], 422);
            }
        } else {
            $google2fa = new Google2FA;
            $valid = $google2fa->verifyKey($user->google2fa_secret, $validated['code']);

            if (! $valid) {
                return response()->json(['message' => 'Invalid two-factor code.'], 422);
            }
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'data' => [
                'token' => $token,
            ],
        ]);
    }
}
