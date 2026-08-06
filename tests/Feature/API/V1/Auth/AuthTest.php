<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['patient', 'pharmacist', 'admin', 'doctor', 'specialist', 'company_owner', 'scientific_rep'] as $role) {
        Role::updateOrCreate(['name' => $role, 'guard_name' => 'api']);
    }
});

// ─── Email Verification: Send ─────────────────────────────────────────

it('sends a verification email for an unverified user', function () {
    Mail::fake();

    $user = User::factory()->create(['email_verified_at' => null]);

    $response = $this->postJson('/api/v1/auth/send-verification-email', [
        'email' => $user->email,
    ]);

    $response->assertOk()->assertJson(['message' => 'Verification email sent.']);

    $tokenRecord = DB::table('email_verification_tokens')->where('email', $user->email)->first();
    expect($tokenRecord)->not->toBeNull();
});

it('returns success for already verified user without sending email', function () {
    Mail::fake();

    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->postJson('/api/v1/auth/send-verification-email', [
        'email' => $user->email,
    ]);

    $response->assertOk()->assertJson(['message' => 'Email is already verified.']);
});

it('validates email exists when sending verification', function () {
    $response = $this->postJson('/api/v1/auth/send-verification-email', [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertStatus(422);
});

// ─── Email Verification: Verify ───────────────────────────────────────

it('verifies email with valid token', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    $rawToken = (string) random_int(100000, 999999);

    DB::table('email_verification_tokens')->insert([
        'email' => $user->email,
        'token' => Hash::make($rawToken),
        'created_at' => now(),
    ]);

    $response = $this->postJson('/api/v1/auth/verify-email', [
        'email' => $user->email,
        'token' => $rawToken,
    ]);

    $response->assertOk()->assertJson(['message' => 'Email verified successfully.']);

    $user->refresh();
    expect($user->email_verified_at)->not->toBeNull();

    $tokenRecord = DB::table('email_verification_tokens')->where('email', $user->email)->first();
    expect($tokenRecord)->toBeNull();
});

it('rejects verification with invalid token', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    DB::table('email_verification_tokens')->insert([
        'email' => $user->email,
        'token' => Hash::make((string) random_int(100000, 999999)),
        'created_at' => now(),
    ]);

    $response = $this->postJson('/api/v1/auth/verify-email', [
        'email' => $user->email,
        'token' => (string) random_int(100000, 999999),
    ]);

    $response->assertStatus(422)->assertJson(['message' => 'Invalid verification token.']);
});

it('rejects verification with expired token', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    $rawToken = (string) random_int(100000, 999999);

    DB::table('email_verification_tokens')->insert([
        'email' => $user->email,
        'token' => Hash::make($rawToken),
        'created_at' => now()->subHours(25),
    ]);

    $response = $this->postJson('/api/v1/auth/verify-email', [
        'email' => $user->email,
        'token' => $rawToken,
    ]);

    $response->assertStatus(422)->assertJson(['message' => 'Verification token has expired. Please request a new one.']);
});

// ─── Forgot Password: Send ────────────────────────────────────────────

it('sends a password reset email', function () {
    Mail::fake();

    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => $user->email,
    ]);

    $response->assertOk()->assertJson(['message' => 'Password reset email sent.']);

    $tokenRecord = DB::table('password_reset_tokens')->where('email', $user->email)->first();
    expect($tokenRecord)->not->toBeNull();
});

it('validates email exists when requesting password reset', function () {
    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertStatus(422);
});

// ─── Forgot Password: Reset ───────────────────────────────────────────

it('resets password with valid token', function () {
    $user = User::factory()->create(['password' => Hash::make('oldpassword')]);
    $rawToken = (string) random_int(100000, 999999);

    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => Hash::make($rawToken),
        'created_at' => now(),
    ]);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'email' => $user->email,
        'token' => $rawToken,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertOk()->assertJson(['message' => 'Password reset successfully.']);

    $user->refresh();
    expect(Hash::check('newpassword123', $user->password))->toBeTrue();

    $tokenRecord = DB::table('password_reset_tokens')->where('email', $user->email)->first();
    expect($tokenRecord)->toBeNull();
});

it('revokes all tokens on password reset', function () {
    $user = User::factory()->create(['password' => Hash::make('oldpassword')]);
    $rawToken = (string) random_int(100000, 999999);
    $user->createToken('test-token');

    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => Hash::make($rawToken),
        'created_at' => now(),
    ]);

    $this->postJson('/api/v1/auth/reset-password', [
        'email' => $user->email,
        'token' => $rawToken,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    expect($user->fresh()->tokens()->count())->toBe(0);
});

it('rejects reset with invalid token', function () {
    $user = User::factory()->create();

    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => Hash::make((string) random_int(100000, 999999)),
        'created_at' => now(),
    ]);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'email' => $user->email,
        'token' => (string) random_int(100000, 999999),
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(422)->assertJson(['message' => 'Invalid reset token.']);
});

it('rejects reset with expired token', function () {
    $user = User::factory()->create();
    $rawToken = (string) random_int(100000, 999999);

    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => Hash::make($rawToken),
        'created_at' => now()->subMinutes(61),
    ]);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'email' => $user->email,
        'token' => $rawToken,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(422)->assertJson(['message' => 'Reset token has expired. Please request a new one.']);
});

it('validates password confirmation matches', function () {
    $user = User::factory()->create();
    $rawToken = (string) random_int(100000, 999999);

    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => Hash::make($rawToken),
        'created_at' => now(),
    ]);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'email' => $user->email,
        'token' => $rawToken,
        'password' => 'newpassword123',
        'password_confirmation' => 'differentpassword',
    ]);

    $response->assertStatus(422);
});

// ─── Login blocked for unverified users ───────────────────────────────

it('blocks login for unverified patient', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'password' => Hash::make('password123'),
    ]);
    $user->assignRole('patient');

    $response = $this->postJson('/api/v1/patient/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(403)->assertJson(['message' => 'Email not verified. Please check your inbox for the verification code.']);
});

it('allows login for verified patient', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'password' => Hash::make('password123'),
    ]);
    $user->assignRole('patient');

    $response = $this->postJson('/api/v1/patient/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertOk()->assertJsonStructure(['data' => ['token']]);
});

it('blocks login for unverified pharmacist', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'password' => Hash::make('password123'),
    ]);
    $user->assignRole('pharmacist');

    $response = $this->postJson('/api/v1/pharmacist/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(403)->assertJson(['message' => 'Email not verified. Please check your inbox for the verification code.']);
});

it('blocks login for unverified admin', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'password' => Hash::make('password123'),
    ]);
    $user->assignRole('admin');

    $response = $this->postJson('/api/v1/admin/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(403)->assertJson(['message' => 'Email not verified. Please check your inbox for the verification code.']);
});

it('blocks login for unverified doctor', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'password' => Hash::make('password123'),
    ]);
    $user->assignRole('doctor');

    $response = $this->postJson('/api/v1/doctor/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(403)->assertJson(['message' => 'Email not verified. Please check your inbox for the verification code.']);
});

it('blocks login for unverified specialist', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'password' => Hash::make('password123'),
    ]);
    $user->assignRole('specialist');

    $response = $this->postJson('/api/v1/specialist/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(403)->assertJson(['message' => 'Email not verified. Please check your inbox for the verification code.']);
});

it('blocks login for unverified company owner', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'password' => Hash::make('password123'),
    ]);
    $user->assignRole('company_owner');

    $response = $this->postJson('/api/v1/company/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(403)->assertJson(['message' => 'Email not verified. Please check your inbox for the verification code.']);
});

it('blocks login for unverified scientific rep', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'password' => Hash::make('password123'),
    ]);
    $user->assignRole('scientific_rep');

    $response = $this->postJson('/api/v1/rep/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(403)->assertJson(['message' => 'Email not verified. Please check your inbox for the verification code.']);
});

// ─── Full flow: Register → Verify → Login ─────────────────────────────

it('completes full patient registration verification and login flow', function () {
    Mail::fake();

    $response = $this->postJson('/api/v1/patient/register', [
        'f_name' => 'Test',
        'l_name' => 'Patient',
        'email' => 'testpatient@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone_number' => '01000000000',
        'age' => 30,
        'gender' => 'male',
        'location' => 'Cairo',
        'latitude' => 30.0444,
        'longitude' => 31.2357,
    ]);

    $response->assertStatus(201)
        ->assertJsonMissing(['data' => ['token']]);

    $user = User::where('email', 'testpatient@example.com')->first();
    expect($user->email_verified_at)->toBeNull();

    $loginResponse = $this->postJson('/api/v1/patient/login', [
        'email' => 'testpatient@example.com',
        'password' => 'password123',
    ]);
    $loginResponse->assertStatus(403);

    $rawToken = (string) random_int(100000, 999999);
    DB::table('email_verification_tokens')->updateOrInsert(
        ['email' => 'testpatient@example.com'],
        ['token' => Hash::make($rawToken), 'created_at' => now()]
    );

    $verifyResponse = $this->postJson('/api/v1/auth/verify-email', [
        'email' => 'testpatient@example.com',
        'token' => $rawToken,
    ]);
    $verifyResponse->assertOk();

    $loginResponse = $this->postJson('/api/v1/patient/login', [
        'email' => 'testpatient@example.com',
        'password' => 'password123',
    ]);
    $loginResponse->assertOk()->assertJsonStructure(['data' => ['token']]);
});

it('completes full forgot password flow', function () {
    Mail::fake();

    $user = User::factory()->create(['password' => Hash::make('oldpassword')]);

    $forgotResponse = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => $user->email,
    ]);
    $forgotResponse->assertOk();

    $rawToken = (string) random_int(100000, 999999);
    DB::table('password_reset_tokens')->updateOrInsert(
        ['email' => $user->email],
        ['token' => Hash::make($rawToken), 'created_at' => now()]
    );

    $resetResponse = $this->postJson('/api/v1/auth/reset-password', [
        'email' => $user->email,
        'token' => $rawToken,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);
    $resetResponse->assertOk();

    $loginResponse = $this->postJson('/api/v1/patient/login', [
        'email' => $user->email,
        'password' => 'newpassword123',
    ]);

    expect(Hash::check('newpassword123', $user->fresh()->password))->toBeTrue();
});
