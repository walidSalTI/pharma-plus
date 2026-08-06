<?php

it('returns a successful response', function () {
    $response = $this->getJson('/api/v1/medications');

    $response->assertStatus(200);
});
