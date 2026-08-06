<?php

declare(strict_types=1);

use App\Models\MedicationProposal;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::updateOrCreate(['name' => 'pharmacist', 'guard_name' => 'api']);
});

it('submits a medication proposal', function () {
    extract(actingAsPharmacist());

    $response = $this->withToken($token)
        ->postJson('/api/v1/pharmacist/proposals', [
            'medication_name' => 'NewDrug 500mg',
            'form' => 'tablet',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'medication_name', 'form', 'status']]);
    expect($response->json('data.status'))->toBe('pending');
});

it('lists pharmacist proposals', function () {
    extract(actingAsPharmacist());

    MedicationProposal::factory()->count(3)->create([
        'pharmacist_id' => $pharmacist->id,
    ]);

    $response = $this->withToken($token)
        ->getJson('/api/v1/pharmacist/proposals');

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'meta']);
    expect(count($response->json('data')))->toBe(3);
});

it('shows a single proposal', function () {
    extract(actingAsPharmacist());

    $proposal = MedicationProposal::factory()->create([
        'pharmacist_id' => $pharmacist->id,
    ]);

    $response = $this->withToken($token)
        ->getJson("/api/v1/pharmacist/proposals/{$proposal->id}");

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['id', 'medication_name', 'status']]);
});
