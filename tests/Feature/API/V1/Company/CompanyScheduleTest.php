<?php

declare(strict_types=1);

use App\Models\Doctor;
use App\Models\ScientificRep;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::updateOrCreate(['name' => 'company_owner', 'guard_name' => 'api']);
    Role::updateOrCreate(['name' => 'scientific_rep', 'guard_name' => 'api']);
});

it('creates a schedule', function () {
    extract(actingAsCompanyOwner());
    $repUser = User::factory()->create();
    $repUser->assignRole('scientific_rep');
    $rep = ScientificRep::create(['user_id' => $repUser->id, 'company_id' => $company->id]);
    $doctor = Doctor::create(['user_id' => User::factory()->create()->id, 'specialization' => 'General']);

    $response = $this->withToken($token)
        ->postJson('/api/v1/company/schedules', [
            'rep_id' => $rep->id,
            'doctor_id' => $doctor->id,
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'notes' => 'Test visit',
        ]);

    $response->assertStatus(201);
});

it('lists schedules', function () {
    extract(actingAsCompanyOwner());

    $response = $this->withToken($token)
        ->getJson('/api/v1/company/schedules');

    $response->assertStatus(200)
        ->assertJsonStructure(['data']);
});

it('creates schedules in batch', function () {
    extract(actingAsCompanyOwner());
    $repUser = User::factory()->create();
    $repUser->assignRole('scientific_rep');
    $rep = ScientificRep::create(['user_id' => $repUser->id, 'company_id' => $company->id]);
    $doctor = Doctor::create(['user_id' => User::factory()->create()->id, 'specialization' => 'General']);

    $response = $this->withToken($token)
        ->postJson('/api/v1/company/schedules/batch', [
            'schedules' => [
                [
                    'rep_id' => $rep->id,
                    'doctor_id' => $doctor->id,
                    'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
                ],
            ],
        ]);

    $response->assertStatus(201);
});
