<?php

declare(strict_types=1);

it('lists visits', function () {
    extract(actingAsCompanyOwner());

    $response = $this->withToken($token)
        ->getJson('/api/v1/company/visits');

    $response->assertStatus(200)
        ->assertJsonStructure(['data']);
});

it('shows visit stats', function () {
    extract(actingAsCompanyOwner());

    $response = $this->withToken($token)
        ->getJson('/api/v1/company/visits/stats');

    $response->assertStatus(200)
        ->assertJsonStructure(['data']);
});
