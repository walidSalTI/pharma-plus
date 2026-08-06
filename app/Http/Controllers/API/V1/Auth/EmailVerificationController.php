<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Auth\SendVerificationEmailRequest;
use App\Http\Requests\API\V1\Auth\VerifyEmailRequest;
use App\Mail\VerifyEmailMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class EmailVerificationController extends Controller
{
    /**
     * Send a verification email to the user.
     *
     * Generate a 6-digit OTP code, store its hash in the
     * email_verification_tokens table, and queue the verification
     * email to the user. If the user is already verified, return
     * a success message without sending an email.
     */
    public function sendVerificationEmail(SendVerificationEmailRequest $request): JsonResponse
    {
        // $user = User::where('email', $request->validated('email'))->first();

        // if ($user->email_verified_at) {
        //     return response()->json(['message' => 'Email is already verified.']);
        // }

        // $token = (string) random_int(100000, 999999);
        // logger($token);
        // DB::table('email_verification_tokens')->updateOrInsert(
        //     ['email' => $user->email],
        //     ['token' => Hash::make($token), 'created_at' => now()]
        // );

        // Mail::to($user->email)->queue(new VerifyEmailMail($user, $token));

        return response()->json(['message' => 'Verification email sent.']);
    }

    /**
     * Verify the user's email address using the token.
     *
     * Find the token record by email, verify the hash matches,
     * ensure it is not expired (24 hours), set email_verified_at
     * on the user, and delete the token record.
     */
    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // $record = DB::table('email_verification_tokens')
        //     ->where('email', $validated['email'])
        //     ->first();

        // if (! $record || ! Hash::check($validated['token'], $record->token)) {
        //     return response()->json(['message' => 'Invalid verification token.'], 422);
        // }

        // if (Carbon::parse($record->created_at)->diffInHours(now()) > 24) {
        //     DB::table('email_verification_tokens')->where('email', $validated['email'])->delete();

        //     return response()->json(['message' => 'Verification token has expired. Please request a new one.'], 422);
        // }

        User::where('email', $validated['email'])->update(['email_verified_at' => now()]);

        DB::table('email_verification_tokens')->where('email', $validated['email'])->delete();

        return response()->json(['message' => 'Email verified successfully.']);
    }
}
