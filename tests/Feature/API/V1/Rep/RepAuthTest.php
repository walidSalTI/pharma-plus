<?php

declare(strict_types=1);

use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::updateOrCreate(['name' => 'scientific_rep', 'guard_name' => 'api']);
    Role::updateOrCreate(['name' => 'company_owner', 'guard_name' => 'api']);
});

it('logs in a scientific rep', function () {
    extract(actingAsScientificRep());

    $response = $this->postJson('/api/v1/rep/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['token']]);
});

it('rejects rep login with wrong password', function () {
    extract(actingAsScientificRep());

    $response = $this->postJson('/api/v1/rep/login', [
        'email' => $user->email,
        'password' => 'wrongpass',
    ]);

    $response->assertStatus(401);
});

it('shows rep dashboard', function () {
    extract(actingAsScientificRep());

    $response = $this->withToken($token)
        ->getJson('/api/v1/rep/dashboard');

    $response->assertStatus(200)
        ->assertJsonStructure(['data']);
});

it('logs out rep', function () {
    extract(actingAsScientificRep());

    $response = $this->withToken($token)
        ->postJson('/api/v1/rep/logout');

    $response->assertStatus(200);
});

it('rejects unauthenticated rep access', function () {
    $response = $this->getJson('/api/v1/rep/dashboard');

    $response->assertStatus(401);
});
