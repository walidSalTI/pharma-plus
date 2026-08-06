<?php

declare(strict_types=1);

use App\Models\Pharmacist;
use App\Models\User;
use App\Notifications\StaffInvitationNotification;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::updateOrCreate(['name' => 'pharmacist', 'guard_name' => 'api']);
});

it('lists notifications', function () {
    extract(actingAsPharmacist());

    $response = $this->withToken($token)
        ->getJson('/api/v1/pharmacist/notifications');

    $response->assertStatus(200)
        ->assertJsonStructure(['data']);
});

it('sends staff invitation to existing pharmacist', function () {
    extract(actingAsPharmacist());

    $targetUser = User::factory()->create();
    $targetUser->assignRole('pharmacist');
    $target = Pharmacist::factory()->create(['user_id' => $targetUser->id]);

    $response = $this->withToken($token)
        ->postJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/staff/invite/{$target->id}", [
            'permissions' => ['inventory_manage' => true],
        ]);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Staff invitation sent successfully.']);
});

it('cannot invite self', function () {
    extract(actingAsPharmacist());

    $response = $this->withToken($token)
        ->postJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/staff/invite/{$pharmacist->id}");

    $response->assertStatus(400)
        ->assertJson(['message' => 'Cannot invite yourself.']);
});

it('cannot invite pharmacy owner', function () {
    extract(actingAsPharmacist());
    $owner = $pharmacy->pharmacist;

    $response = $this->withToken($token)
        ->postJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/staff/invite/{$owner->id}");

    $response->assertStatus(400)
        ->assertJson(['message' => 'Cannot invite yourself.']);
});

it('accepts a staff invitation', function () {
    extract(actingAsPharmacist());

    $targetUser = User::factory()->create();
    $targetUser->assignRole('pharmacist');
    $target = Pharmacist::factory()->create(['user_id' => $targetUser->id]);
    $targetToken = $targetUser->createToken('test')->plainTextToken;

    $targetUser->notify(new StaffInvitationNotification(
        pharmacy: $pharmacy,
        inviter: $pharmacist,
        permissions: ['inventory_manage' => true],
    ));

    $notification = $targetUser->notifications()->first();

    $response = $this->withToken($targetToken)
        ->postJson("/api/v1/pharmacist/notifications/{$notification->id}/accept-invitation");

    $response->assertStatus(200);
    expect($target->staffPharmacies()->where('pharmacy_id', $pharmacy->id)->exists())->toBeTrue();
});

it('rejects a staff invitation', function () {
    extract(actingAsPharmacist());

    $targetUser = User::factory()->create();
    $targetUser->assignRole('pharmacist');
    $target = Pharmacist::factory()->create(['user_id' => $targetUser->id]);
    $targetToken = $targetUser->createToken('test')->plainTextToken;

    $targetUser->notify(new StaffInvitationNotification(
        pharmacy: $pharmacy,
        inviter: $pharmacist,
        permissions: [],
    ));

    $notification = $targetUser->notifications()->first();

    $response = $this->withToken($targetToken)
        ->postJson("/api/v1/pharmacist/notifications/{$notification->id}/reject-invitation");

    $response->assertStatus(200);
    expect($target->staffPharmacies()->where('pharmacy_id', $pharmacy->id)->exists())->toBeFalse();
});

it('sends a join request as non-owner pharmacist', function () {
    extract(actingAsPharmacist());

    $otherUser = User::factory()->create();
    $otherUser->assignRole('pharmacist');
    $other = Pharmacist::factory()->create(['user_id' => $otherUser->id]);
    $otherToken = $otherUser->createToken('test')->plainTextToken;

    $response = $this->withToken($otherToken)
        ->postJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/join-request");

    $response->assertStatus(200);
});
