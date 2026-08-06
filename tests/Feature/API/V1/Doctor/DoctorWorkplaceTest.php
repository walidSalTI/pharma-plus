<?php

declare(strict_types=1);

use App\Models\DoctorWorkplace;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::updateOrCreate(['name' => 'doctor', 'guard_name' => 'api']);
});

it('lists doctor workplaces', function () {
    extract(actingAsDoctor());

    $response = $this->withToken($token)
        ->getJson('/api/v1/doctor/workplaces');

    $response->assertStatus(200)
        ->assertJsonStructure(['data']);
});

it('creates a new workplace', function () {
    extract(actingAsDoctor());

    $response = $this->withToken($token)
        ->postJson('/api/v1/doctor/workplaces', [
            'place_name' => 'New Clinic',
            'place_type' => 'clinic',
            'latitude' => 30.0500,
            'longitude' => 31.2400,
            'radius_meters' => 100,
        ]);

    $response->assertStatus(201);
});

it('updates a workplace', function () {
    extract(actingAsDoctor());

    $response = $this->withToken($token)
        ->putJson('/api/v1/doctor/workplaces/'.$workplace->id, [
            'place_name' => 'Updated Clinic',
        ]);

    $response->assertStatus(200);
    expect(DoctorWorkplace::find($workplace->id)->place_name)->toBe('Updated Clinic');
});

it('deletes a workplace', function () {
    extract(actingAsDoctor());

    $response = $this->withToken($token)
        ->deleteJson('/api/v1/doctor/workplaces/'.$workplace->id);

    $response->assertStatus(200);
    expect(DoctorWorkplace::find($workplace->id))->toBeNull();
});
