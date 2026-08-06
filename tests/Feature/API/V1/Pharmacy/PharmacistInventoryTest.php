<?php

declare(strict_types=1);

use App\Models\Medication;
use App\Models\PharmacyInventory;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::updateOrCreate(['name' => 'pharmacist', 'guard_name' => 'api']);
});

it('lists inventory items', function () {
    extract(actingAsPharmacist());
    $medication = Medication::factory()->create();
    PharmacyInventory::factory()->create([
        'pharmacy_id' => $pharmacy->id,
        'medication_id' => $medication->id,
    ]);

    $response = $this->withToken($token)
        ->getJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/inventory");

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'meta']);
});

it('adds inventory items', function () {
    extract(actingAsPharmacist());
    $medication1 = Medication::factory()->create();
    $medication2 = Medication::factory()->create();

    $response = $this->withToken($token)
        ->postJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/inventory", [
            'items' => [
                [
                    'medication_id' => $medication1->id,
                    'price' => 49.99,
                    'stock' => 100,
                    'min_stock' => 10,
                ],
                [
                    'medication_id' => $medication2->id,
                    'price' => 99.99,
                    'stock' => 50,
                ],
            ],
        ]);

    $response->assertStatus(201)
        ->assertJsonCount(2, 'data');
});

it('skips duplicate inventory items', function () {
    extract(actingAsPharmacist());
    $medication = Medication::factory()->create();
    PharmacyInventory::factory()->create([
        'pharmacy_id' => $pharmacy->id,
        'medication_id' => $medication->id,
    ]);

    $response = $this->withToken($token)
        ->postJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/inventory", [
            'items' => [
                [
                    'medication_id' => $medication->id,
                    'price' => 49.99,
                    'stock' => 50,
                ],
            ],
        ]);

    $response->assertStatus(200);
    expect($response->json('skipped'))->toHaveCount(1);
});

it('updates inventory items in bulk', function () {
    extract(actingAsPharmacist());
    $medication1 = Medication::factory()->create();
    $medication2 = Medication::factory()->create();
    PharmacyInventory::factory()->create([
        'pharmacy_id' => $pharmacy->id,
        'medication_id' => $medication1->id,
        'stock' => 50,
    ]);
    PharmacyInventory::factory()->create([
        'pharmacy_id' => $pharmacy->id,
        'medication_id' => $medication2->id,
        'stock' => 30,
    ]);

    $response = $this->withToken($token)
        ->putJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/inventory", [
            'items' => [
                [
                    'medication_id' => $medication1->id,
                    'stock' => 200,
                    'price' => 39.99,
                ],
                [
                    'medication_id' => $medication2->id,
                    'stock' => 75,
                ],
            ],
        ]);

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(2);
    expect($response->json('data.0.stock'))->toBe(200);
    expect($response->json('data.1.stock'))->toBe(75);
});

it('updates a single inventory item', function () {
    extract(actingAsPharmacist());
    $inventory = PharmacyInventory::factory()->create([
        'pharmacy_id' => $pharmacy->id,
        'medication_id' => Medication::factory()->create()->id,
        'stock' => 50,
    ]);

    $response = $this->withToken($token)
        ->putJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/inventory/{$inventory->id}", [
            'stock' => 200,
            'price' => 39.99,
        ]);

    $response->assertStatus(200);
    expect($response->json('data.stock'))->toBe(200);
});

it('deletes inventory item', function () {
    extract(actingAsPharmacist());
    $inventory = PharmacyInventory::factory()->create([
        'pharmacy_id' => $pharmacy->id,
        'medication_id' => Medication::factory()->create()->id,
    ]);

    $response = $this->withToken($token)
        ->deleteJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/inventory/{$inventory->id}");

    $response->assertStatus(200);
    expect(PharmacyInventory::find($inventory->id))->toBeNull();
});

it('filters inventory by low stock', function () {
    extract(actingAsPharmacist());
    $medication = Medication::factory()->create();
    PharmacyInventory::factory()->create([
        'pharmacy_id' => $pharmacy->id,
        'medication_id' => $medication->id,
        'stock' => 2,
        'min_stock' => 10,
    ]);

    $response = $this->withToken($token)
        ->getJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/inventory?low_stock=1");

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(1);
});
