<?php

declare(strict_types=1);

use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::updateOrCreate(['name' => 'pharmacist', 'guard_name' => 'api']);
});

it('lists operating hours', function () {
    extract(actingAsPharmacist());

    $response = $this->withToken($token)
        ->getJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/operating-hours");

    $response->assertStatus(200)
        ->assertJsonStructure(['data']);
});

it('upserts operating hours', function () {
    extract(actingAsPharmacist());

    $hours = [];
    for ($day = 0; $day <= 6; $day++) {
        $hours[] = [
            'day_of_week' => $day,
            'opening_time' => '08:00',
            'closing_time' => '20:00',
            'is_24_hours' => false,
            'is_closed' => $day === 5,
        ];
    }

    $response = $this->withToken($token)
        ->putJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/operating-hours", ['hours' => $hours]);

    $response->assertStatus(200)
        ->assertJsonStructure(['data']);
    expect(count($response->json('data')))->toBe(7);
});

it('declares vacation closure', function () {
    extract(actingAsPharmacist());

    $response = $this->withToken($token)
        ->postJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/vacation", [
            'start_day' => 0,
            'end_day' => 2,
            'is_closed' => true,
        ]);

    $response->assertStatus(200);
});
