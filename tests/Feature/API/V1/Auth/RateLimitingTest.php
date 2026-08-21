<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::updateOrCreate(['name' => 'patient', 'guard_name' => 'api']);
});

// ─── Login Limiter (5/min per IP) ─────────────────────────────────────

it('blocks the 6th login attempt within one minute', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    foreach (range(1, 5) as $i) {
        $this->postJson('/api/v1/patient/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(401);
    }

    $response = $this->postJson('/api/v1/patient/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(429);
    expect($response->headers->get('Retry-After'))->not->toBeNull();
});

it('allows login again after the rate limit window expires', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'password' => 'correct-password',
    ]);

    foreach (range(1, 5) as $i) {
        $this->postJson('/api/v1/patient/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(401);
    }

    $this->postJson('/api/v1/patient/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertStatus(429);

    $this->travel(1)->minutes();

    $this->postJson('/api/v1/patient/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertStatus(401);
});

// ─── OTP Request Limiter (1/min per email) ────────────────────────────

it('blocks a second otp request for the same email within one minute', function () {
    Mail::fake();

    $user = User::factory()->create(['email_verified_at' => null]);

    $this->postJson('/api/v1/auth/send-verification-email', [
        'email' => $user->email,
    ])->assertOk();

    $this->postJson('/api/v1/auth/send-verification-email', [
        'email' => $user->email,
    ])->assertStatus(429);
});

it('allows otp requests for different emails independently', function () {
    Mail::fake();

    $first = User::factory()->create(['email_verified_at' => null]);
    $second = User::factory()->create(['email_verified_at' => null]);

    $this->postJson('/api/v1/auth/send-verification-email', [
        'email' => $first->email,
    ])->assertOk();

    $this->postJson('/api/v1/auth/send-verification-email', [
        'email' => $second->email,
    ])->assertOk();
});

it('throttles repeated registration otp emails from the same ip', function () {
    Mail::fake();

    $payload = fn (string $email): array => [
        'f_name' => 'Test',
        'l_name' => 'Patient',
        'email' => $email,
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone_number' => '+963912345678',
        'age' => 30,
        'gender' => 'male',
        'latitude' => 33.5138,
        'longitude' => 36.2765,
    ];

    // Per-email limit never triggers (unique emails), but the per-IP limit (5/min) does.
    foreach (range(1, 5) as $i) {
        $this->postJson('/api/v1/patient/register', $payload("user{$i}@example.com"))
            ->assertCreated();
    }

    $this->postJson('/api/v1/patient/register', $payload('sixth@example.com'))
        ->assertStatus(429);
});

// ─── Public Search Limiter (30/min per user or IP) ────────────────────

it('allows 30 public searches per minute and blocks the 31st', function () {
    $searchPayload = [
        'queries' => ['panadol'],
        'latitude' => 33.5138,
        'longitude' => 36.2765,
    ];

    foreach (range(1, 30) as $i) {
        $this->postJson('/api/v1/patient/search', $searchPayload)->assertOk();
    }

    $this->postJson('/api/v1/patient/search', $searchPayload)->assertStatus(429);
});
