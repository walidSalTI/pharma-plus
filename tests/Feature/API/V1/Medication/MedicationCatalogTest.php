<?php

declare(strict_types=1);

use App\Models\Medication;
use App\Models\Product;

it('lists medications publicly', function () {
    Medication::factory()->count(5)->create();

    $response = $this->getJson('/api/v1/medications');

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'meta']);
    expect(count($response->json('data')))->toBe(5);
});

it('filters medications by name', function () {
    $product1 = Product::create(['name' => 'Paracetamol 500mg', 'type' => 'medication']);
    $product2 = Product::create(['name' => 'Ibuprofen 200mg', 'type' => 'medication']);

    Medication::factory()->create(['product_id' => $product1->id]);
    Medication::factory()->create(['product_id' => $product2->id]);

    $response = $this->getJson('/api/v1/medications?name=Paracetamol');

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(1);
    expect($response->json('data.0.trade_name'))->toBe('Paracetamol 500mg');
});
