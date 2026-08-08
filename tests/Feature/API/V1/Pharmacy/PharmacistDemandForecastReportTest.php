<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::updateOrCreate(['name' => 'pharmacist', 'guard_name' => 'api']);
});

it('returns demand map data', function () {
    extract(actingAsPharmacist());

    $response = $this->withToken($token)
        ->getJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/demand-map");

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'meta']);
});

it('returns disease forecasts', function () {
    extract(actingAsPharmacist());

    $response = $this->withToken($token)
        ->getJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/disease-forecasts");

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'meta']);
});

it('returns the financial summary report', function () {
    extract(actingAsPharmacist());

    $today = now()->toDateString();

    $response = $this->withToken($token)
        ->getJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/reports/financial-summary?start_date={$today}&end_date={$today}");

    $response->assertStatus(200)
        ->assertJsonStructure(['status', 'data']);
});

it('exports inventory', function () {
    extract(actingAsPharmacist());

    $response = $this->withToken($token)
        ->getJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/inventory/export");

    $response->assertStatus(200);
});

it('bulk imports inventory', function () {
    extract(actingAsPharmacist());
    $file = UploadedFile::fake()->create('inventory.xlsx', 1024);

    $response = $this->withToken($token)
        ->postJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/inventory/bulk-import", [
            'file' => $file,
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['file_path']]);
});
