<?php

declare(strict_types=1);

use App\Models\Doctor;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::updateOrCreate(['name' => 'doctor', 'guard_name' => 'api']);
});

it('returns the secret key for a verified doctor', function () {
    extract(actingAsDoctor());

    $response = $this->withToken($token)
        ->getJson('/api/v1/doctor/qr/secret-key');

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['doctor_id', 'secret_key']])
        ->assertJsonPath('data.doctor_id', $doctor->id);

    // Secret key should be a base32-encoded string (typical TOTP format)
    expect($response->json('data.secret_key'))->toBeString()->not->toBeEmpty();
});

it('auto-generates a secret key when the doctor has none', function () {
    $user = User::factory()->create();
    $user->assignRole('doctor');
    $doctor = Doctor::create([
        'user_id' => $user->id,
        'specialization' => 'Neurology',
        'doctor_secret_key' => null,
        'verification_status' => 'approved',
    ]);
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/doctor/qr/secret-key');

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['doctor_id', 'secret_key']]);

    // Verify the key was persisted in the database
    $doctor->refresh();
    expect($doctor->doctor_secret_key)->not->toBeNull();
});

it('returns the same secret key on subsequent requests', function () {
    extract(actingAsDoctor());

    $first = $this->withToken($token)
        ->getJson('/api/v1/doctor/qr/secret-key');

    $second = $this->withToken($token)
        ->getJson('/api/v1/doctor/qr/secret-key');

    expect($first->json('data.secret_key'))
        ->toBe($second->json('data.secret_key'));
});

it('rejects access without authentication', function () {
    $response = $this->getJson('/api/v1/doctor/qr/secret-key');

    $response->assertStatus(401);
});

it('rejects access when doctor is not verified', function () {
    $user = User::factory()->create();
    $user->assignRole('doctor');
    Doctor::create([
        'user_id' => $user->id,
        'specialization' => 'General',
        'doctor_secret_key' => 'test-secret',
        'verification_status' => 'unverified',
    ]);
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/doctor/qr/secret-key');

    $response->assertStatus(403);
});

it('rejects access when doctor profile is missing', function () {
    // User has the doctor role but no Doctor record exists.
    // The VerifiedDoctor middleware catches this first and returns 403
    // before the controller can return 404.
    $user = User::factory()->create();
    $user->assignRole('doctor');
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/doctor/qr/secret-key');

    $response->assertStatus(403);
});
