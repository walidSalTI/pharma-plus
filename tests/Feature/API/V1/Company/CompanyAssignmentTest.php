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

it('creates an assignment', function () {
    extract(actingAsCompanyOwner());
    $repUser = User::factory()->create();
    $repUser->assignRole('scientific_rep');
    $rep = ScientificRep::create(['user_id' => $repUser->id, 'company_id' => $company->id]);
    $doctor = Doctor::create(['user_id' => User::factory()->create()->id, 'specialization' => 'General']);

    $response = $this->withToken($token)
        ->postJson('/api/v1/company/assignments', [
            'rep_id' => $rep->id,
            'doctor_id' => $doctor->id,
        ]);

    $response->assertStatus(201);
});

it('lists assignments', function () {
    extract(actingAsCompanyOwner());

    $response = $this->withToken($token)
        ->getJson('/api/v1/company/assignments');

    $response->assertStatus(200)
        ->assertJsonStructure(['data']);
});
