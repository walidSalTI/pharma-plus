<?php

declare(strict_types=1);

use App\Models\Pharmacist;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::updateOrCreate(['name' => 'pharmacist', 'guard_name' => 'api']);
});

it('lists staff members', function () {
    extract(actingAsPharmacist());

    $staffUser = User::factory()->create();
    $staffUser->assignRole('pharmacist');
    $staff = Pharmacist::factory()->create(['user_id' => $staffUser->id]);
    $staff->staffPharmacies()->attach($pharmacy->id);

    $response = $this->withToken($token)
        ->getJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/staff");

    $response->assertStatus(200)
        ->assertJsonStructure(['data']);
});

it('creates a staff member with permissions', function () {
    extract(actingAsPharmacist());

    $response = $this->withToken($token)
        ->postJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/staff", [
            'f_name' => 'Staff',
            'l_name' => 'User',
            'email' => 'staff@pharmacy.ma',
            'password' => 'password123',
            'phone_number' => '+212611111111',
            'permissions' => [
                'inventory_manage' => true,
                'orders_process' => true,
            ],
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'name', 'email', 'permissions']]);
    expect($response->json('data.permissions.inventory_manage'))->toBeTrue();
    expect($response->json('data.permissions.pharmacy_manage'))->toBeFalse();
});

it('searches for an existing pharmacist', function () {
    extract(actingAsPharmacist());

    $targetUser = User::factory()->create();
    $targetUser->assignRole('pharmacist');
    $target = Pharmacist::factory()->create(['user_id' => $targetUser->id]);

    $response = $this->withToken($token)
        ->postJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/staff/search", [
            'query' => $target->id,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'found' => true,
            'already_staff' => false,
            'is_owner' => false,
            'is_self' => false,
        ]);
});

it('searches by email', function () {
    extract(actingAsPharmacist());

    $targetUser = User::factory()->create(['email' => 'findme@test.ma']);
    $targetUser->assignRole('pharmacist');
    Pharmacist::factory()->create(['user_id' => $targetUser->id]);

    $response = $this->withToken($token)
        ->postJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/staff/search", [
            'query' => 'findme@test.ma',
        ]);

    $response->assertStatus(200)
        ->assertJson(['found' => true]);
});

it('returns not found when searching non-existent pharmacist', function () {
    extract(actingAsPharmacist());

    $response = $this->withToken($token)
        ->postJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/staff/search", [
            'query' => 'nonexistent-id',
        ]);

    $response->assertStatus(200)
        ->assertJson(['found' => false]);
});

it('updates staff permissions', function () {
    extract(actingAsPharmacist());

    $staffUser = User::factory()->create();
    $staffUser->assignRole('pharmacist');
    $staff = Pharmacist::factory()->create(['user_id' => $staffUser->id]);
    $staff->staffPharmacies()->attach($pharmacy->id, ['inventory_manage' => true]);

    $response = $this->withToken($token)
        ->putJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/staff/{$staff->id}", [
            'permissions' => [
                'pharmacy_manage' => true,
                'inventory_manage' => false,
            ],
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['permissions']]);
});

it('removes a staff member without deleting account', function () {
    extract(actingAsPharmacist());

    $staffUser = User::factory()->create();
    $staffUser->assignRole('pharmacist');
    $staff = Pharmacist::factory()->create(['user_id' => $staffUser->id]);
    $staff->staffPharmacies()->attach($pharmacy->id);

    $response = $this->withToken($token)
        ->deleteJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/staff/{$staff->id}");

    $response->assertStatus(200);
    expect($staff->fresh())->not->toBeNull();
    expect(User::find($staffUser->id))->not->toBeNull();
    expect($staff->staffPharmacies()->where('pharmacy_id', $pharmacy->id)->exists())->toBeFalse();
});

it('prevents removing non-staff pharmacist', function () {
    extract(actingAsPharmacist());

    $otherUser = User::factory()->create();
    $otherUser->assignRole('pharmacist');
    $other = Pharmacist::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->withToken($token)
        ->deleteJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/staff/{$other->id}");

    $response->assertStatus(400)
        ->assertJson(['message' => 'This pharmacist is not a staff member of this pharmacy.']);
});

it('prevents self-removal from staff', function () {
    extract(actingAsPharmacist());

    $response = $this->withToken($token)
        ->deleteJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/staff/{$pharmacist->id}");

    $response->assertStatus(403)
        ->assertJson(['message' => 'Cannot remove yourself from staff.']);
});
