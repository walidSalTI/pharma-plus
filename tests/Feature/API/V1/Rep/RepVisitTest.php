<?php

declare(strict_types=1);

use App\Models\Doctor;
use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::updateOrCreate(['name' => 'scientific_rep', 'guard_name' => 'api']);
    Role::updateOrCreate(['name' => 'company_owner', 'guard_name' => 'api']);
});

it('rejects check-in with invalid TOTP', function () {
    extract(actingAsScientificRep());
    $doctor = Doctor::create([
        'user_id' => User::factory()->create()->id,
        'specialization' => 'General',
        'doctor_secret_key' => (new Google2FA)->generateSecretKey(),
    ]);

    $response = $this->withToken($token)
        ->postJson('/api/v1/rep/visits/check-in', [
            'doctor_id' => $doctor->id,
            'code' => '000000',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'schedule_id' => 'non-existent',
        ]);

    $response->assertStatus(422);
});

it('rejects unauthenticated check-in', function () {
    $response = $this->postJson('/api/v1/rep/visits/check-in', [
        'doctor_id' => 'test',
        'code' => '000000',
        'latitude' => 30.0444,
        'longitude' => 31.2357,
    ]);

    $response->assertStatus(401);
});

it('lists rep visit history', function () {
    extract(actingAsScientificRep());

    $response = $this->withToken($token)
        ->getJson('/api/v1/rep/visits');

    $response->assertStatus(200)
        ->assertJsonStructure(['data']);
});

it('shows rep visit stats', function () {
    extract(actingAsScientificRep());

    $response = $this->withToken($token)
        ->getJson('/api/v1/rep/visits/stats');

    $response->assertStatus(200)
        ->assertJsonStructure(['data']);
});
