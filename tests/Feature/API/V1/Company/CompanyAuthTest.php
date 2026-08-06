<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::updateOrCreate(['name' => 'company_owner', 'guard_name' => 'api']);
    Role::updateOrCreate(['name' => 'admin', 'guard_name' => 'api']);
    Role::updateOrCreate(['name' => 'scientific_rep', 'guard_name' => 'api']);
});

it('registers a new company with user', function () {
    $response = $this->postJson('/api/v1/company/register', [
        'f_name' => 'Ahmed',
        'l_name' => 'Alami',
        'email' => 'owner@company.ma',
        'password' => 'securepass123',
        'password_confirmation' => 'securepass123',
        'phone_number' => '+212612345678',
        'age' => 35,
        'gender' => 'male',
        'commercial_name' => 'Pharma Test Co',
        'commercial_registration' => 'CR-TEST-001',
        'address' => '123 Test St',
        'phone' => '+201234567890',
        'license_number' => 'LIC-TEST-001',
        'license_image' => UploadedFile::fake()->create('license.jpg', 2048, 'image/jpeg'),
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['message', 'data' => ['company']])
        ->assertJsonMissing(['data' => ['token']]);

    expect(User::where('email', 'owner@company.ma')->exists())->toBeTrue();
});

it('shows company profile', function () {
    extract(actingAsCompanyOwner());

    $response = $this->withToken($token)
        ->getJson('/api/v1/company/profile');

    $response->assertStatus(200)
        ->assertJsonStructure(['data']);
});

it('rejects unauthenticated company access', function () {
    $response = $this->getJson('/api/v1/company/profile');

    $response->assertStatus(401);
});
