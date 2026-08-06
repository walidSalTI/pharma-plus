<?php

declare(strict_types=1);

use App\Models\PharmaceuticalCompany;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::updateOrCreate(['name' => 'admin', 'guard_name' => 'api']);
    Role::updateOrCreate(['name' => 'company_owner', 'guard_name' => 'api']);
});

it('lists pending companies as admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $token = $admin->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/company/admin/pending');

    $response->assertStatus(200)
        ->assertJsonStructure(['data']);
});

it('verifies a company as admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $token = $admin->createToken('test')->plainTextToken;
    $company = PharmaceuticalCompany::create([
        'owner_id' => User::factory()->create()->id,
        'commercial_name' => 'Test Co',
        'commercial_registration' => 'CR-VERIFY',
        'address' => 'Addr',
        'phone' => '01000000000',
        'license_number' => 'LIC-VERIFY',
        'license_image' => 'test.jpg',
        'status' => 'pending',
    ]);

    $response = $this->withToken($token)
        ->postJson('/api/v1/company/admin/'.$company->id.'/verify', [
            'status' => 'active',
        ]);

    $response->assertStatus(200);
    expect(PharmaceuticalCompany::find($company->id)->status)->toBe('active');
});

it('rejects non-admin company verification', function () {
    extract(actingAsCompanyOwner());
    $target = PharmaceuticalCompany::create([
        'owner_id' => User::factory()->create()->id,
        'commercial_name' => 'Other Co',
        'commercial_registration' => 'CR-OTHER',
        'address' => 'Addr',
        'phone' => '01000000001',
        'license_number' => 'LIC-OTHER',
        'license_image' => 'test.jpg',
        'status' => 'pending',
    ]);

    $response = $this->withToken($token)
        ->postJson('/api/v1/company/admin/'.$target->id.'/verify', [
            'status' => 'active',
        ]);

    $response->assertStatus(403);
});
