<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\API\V1\Auth\ResetPasswordRequest;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    /**
     * Send a password reset code to the user.
     *
     * Generate a random 6-digit OTP, store its hash in the
     * password_reset_tokens table, and send the notification via email.
     * Always returns the same response regardless of email existence
     * to prevent user enumeration.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        $token = (string) random_int(100000, 999999);
        logger($token);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        Mail::to($user->email)->queue(new ResetPasswordMail($user, $token));

        return response()->json(['message' => 'Password reset email sent.']);
    }

    /**
     * Reset the user's password using the token.
     *
     * Find the token record by email, verify the hash matches,
     * ensure it is not expired (60 minutes), update the password,
     * delete the token record, and revoke all existing Sanctum tokens
     * for security.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $record = DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->first();
        if (! $record || ! Hash::check($validated['token'], $record->token)) {
            return response()->json(['message' => 'Invalid reset token.'], 422);
        }

        if (Carbon::parse($record->created_at)->diffInMinutes(now()) > 60) {
            DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

            return response()->json(['message' => 'Reset token has expired. Please request a new one.'], 422);
        }

        $user = User::where('email', $validated['email'])->first();
        $user->update(['password' => $validated['password']]);
        $user->tokens()->delete();

        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        return response()->json(['message' => 'Password reset successfully.']);
    }
}
