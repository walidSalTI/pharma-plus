<?php

declare(strict_types=1);

use App\Models\Patient;
use App\Models\PharmacyReview;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::updateOrCreate(['name' => 'pharmacist', 'guard_name' => 'api']);
});

it('lists pharmacy reviews', function () {
    extract(actingAsPharmacist());

    $patientUser = User::factory()->create();
    $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

    PharmacyReview::factory()->count(2)->create([
        'pharmacy_id' => $pharmacy->id,
        'patient_id' => $patient->id,
    ]);

    $response = $this->withToken($token)
        ->getJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/reviews");

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'meta']);
    expect(count($response->json('data')))->toBe(2);
});

it('replies to a review', function () {
    extract(actingAsPharmacist());

    $patientUser = User::factory()->create();
    $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

    $review = PharmacyReview::factory()->create([
        'pharmacy_id' => $pharmacy->id,
        'patient_id' => $patient->id,
        'comment' => 'Great service!',
    ]);

    $response = $this->withToken($token)
        ->postJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/reviews/{$review->id}/reply", [
            'comment' => 'Thank you for your feedback!',
        ]);

    $response->assertStatus(200);
    expect($response->json('data.pharmacist_reply'))->toBe('Thank you for your feedback!');
});
