<?php

declare(strict_types=1);

use App\Models\ScientificRep;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::updateOrCreate(['name' => 'company_owner', 'guard_name' => 'api']);
    Role::updateOrCreate(['name' => 'scientific_rep', 'guard_name' => 'api']);
});

it('creates a new rep', function () {
    extract(actingAsCompanyOwner());

    $response = $this->withToken($token)
        ->postJson('/api/v1/company/reps', [
            'f_name' => 'Ahmed',
            'l_name' => 'Ali',
            'email' => 'ahmed.ali@rep.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone_number' => '01000000001',
            'age' => 28,
            'gender' => 'male',
        ]);

    $response->assertStatus(201);
    expect(User::where('email', 'ahmed.ali@rep.com')->exists())->toBeTrue();
});

it('lists reps', function () {
    extract(actingAsCompanyOwner());

    $response = $this->withToken($token)
        ->getJson('/api/v1/company/reps');

    $response->assertStatus(200)
        ->assertJsonStructure(['data']);
});

it('suspends a rep', function () {
    extract(actingAsCompanyOwner());
    $repUser = User::factory()->create();
    $repUser->assignRole('scientific_rep');
    $rep = ScientificRep::create(['user_id' => $repUser->id, 'company_id' => $company->id]);

    $response = $this->withToken($token)
        ->postJson('/api/v1/company/reps/'.$rep->id.'/suspend');

    $response->assertStatus(200);
});

it('activates a rep', function () {
    extract(actingAsCompanyOwner());
    $repUser = User::factory()->create();
    $repUser->assignRole('scientific_rep');
    $rep = ScientificRep::create(['user_id' => $repUser->id, 'company_id' => $company->id]);

    $this->withToken($token)->postJson('/api/v1/company/reps/'.$rep->id.'/suspend');

    $response = $this->withToken($token)
        ->postJson('/api/v1/company/reps/'.$rep->id.'/activate');

    $response->assertStatus(200);
});

it('deletes a rep', function () {
    extract(actingAsCompanyOwner());
    $repUser = User::factory()->create();
    $repUser->assignRole('scientific_rep');
    $rep = ScientificRep::create(['user_id' => $repUser->id, 'company_id' => $company->id]);

    $response = $this->withToken($token)
        ->deleteJson('/api/v1/company/reps/'.$rep->id);

    $response->assertStatus(200);
});
