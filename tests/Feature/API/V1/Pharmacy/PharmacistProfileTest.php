<?php

declare(strict_types=1);

use App\Models\Medication;
use App\Models\Pharmacist;
use App\Models\PharmacyInventory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::updateOrCreate(['name' => 'pharmacist', 'guard_name' => 'api']);
});

it('returns dashboard data for authenticated pharmacist', function () {
    extract(actingAsPharmacist());

    Medication::factory(3)->create()->each(function ($med) use ($pharmacy) {
        PharmacyInventory::factory()->create([
            'pharmacy_id' => $pharmacy->id,
            'medication_id' => $med->id,
        ]);
    });

    $response = $this->withToken($token)
        ->getJson('/api/v1/pharmacist/dashboard');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'user',
                'pharmacist',
                'pharmacies',
            ],
        ]);
});

it('returns 404 for pharmacist with no pharmacy', function () {
    $user = User::factory()->create();
    $user->assignRole('pharmacist');
    Pharmacist::factory()->create(['user_id' => $user->id]);
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/pharmacist/dashboard');

    $response->assertStatus(200);
});

it('creates a new pharmacy', function () {
    extract(actingAsPharmacist());

    $pharmacist->update(['verification_status' => 'approved']);

    $response = $this->withToken($token)
        ->postJson('/api/v1/pharmacist/pharmacy', [
            'name' => 'New Pharmacy',
            'address' => '123 Main St',
            'latitude' => 33.5731,
            'longitude' => -7.5898,
            'support_email' => 'info@newpharmacy.ma',
            'support_number' => '+212600000000',
            'front_image' => UploadedFile::fake()->create('store.jpg', 1024),
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'name']]);
});

it('shows pharmacy full details', function () {
    extract(actingAsPharmacist());

    $response = $this->withToken($token)
        ->getJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}");

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['id', 'name', 'address']]);
});

it('shows pharmacy profile', function () {
    extract(actingAsPharmacist());

    $response = $this->withToken($token)
        ->getJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/profile");

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['id', 'name', 'address', 'staff_count', 'pending_orders_count', 'low_stock_count']]);
});

it('updates pharmacy profile', function () {
    extract(actingAsPharmacist());

    $response = $this->withToken($token)
        ->putJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/profile", [
            'name' => 'Updated Pharmacy',
            'address' => 'New Address',
            'support_email' => 'new@pharmacy.ma',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'name' => 'Updated Pharmacy',
                'address' => 'New Address',
                'support_email' => 'new@pharmacy.ma',
            ],
        ]);
});

it('submits syndicate card for verification', function () {
    extract(actingAsPharmacist());

    $response = $this->withToken($token)
        ->postJson('/api/v1/pharmacist/verify', [
            'syndicate_card' => UploadedFile::fake()->create('card.pdf', 1024, 'application/pdf'),
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['verification_status']]);
});

it('returns verification status', function () {
    extract(actingAsPharmacist());

    $response = $this->withToken($token)
        ->getJson('/api/v1/pharmacist/verification-status');

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['verification_status']]);
});

it('updates pharmacist user profile', function () {
    extract(actingAsPharmacist());

    $response = $this->withToken($token)
        ->putJson('/api/v1/pharmacist/update-profile', [
            'f_name' => 'Updated',
            'l_name' => 'Name',
            'phone_number' => '+212611111111',
        ]);

    $response->assertStatus(200);
    expect($response->json('data.f_name'))->toBe('Updated');
});
