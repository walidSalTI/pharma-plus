<?php

declare(strict_types=1);

use App\Models\Pharmacist;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::updateOrCreate(['name' => 'pharmacist', 'guard_name' => 'api']);
});

it('registers a new pharmacist', function () {
    $response = $this->postJson('/api/v1/pharmacist/register', [
        'f_name' => 'Ahmed',
        'l_name' => 'Alami',
        'email' => 'ahmed@pharmacy.ma',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone_number' => '+212612345678',
        'age' => 30,
        'gender' => 'male',
        'location' => 'Casablanca',
        'syndicate_card' => UploadedFile::fake()->create('card.pdf', 1024, 'application/pdf'),
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'data' => ['user', 'pharmacist'],
        ])
        ->assertJsonMissing(['data' => ['token']]);

    expect(User::where('email', 'ahmed@pharmacy.ma')->exists())->toBeTrue();
});

it('rejects registration with duplicate email', function () {
    User::factory()->create(['email' => 'dup@test.ma']);

    $response = $this->postJson('/api/v1/pharmacist/register', [
        'f_name' => 'Test',
        'l_name' => 'User',
        'email' => 'dup@test.ma',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone_number' => '+212600000000',
        'age' => 25,
        'gender' => 'female',
        'syndicate_card' => UploadedFile::fake()->create('card.pdf', 1024, 'application/pdf'),
    ]);

    $response->assertStatus(422);
});

it('rejects registration with weak password', function () {
    $response = $this->postJson('/api/v1/pharmacist/register', [
        'f_name' => 'Test',
        'l_name' => 'User',
        'email' => 'test@test.ma',
        'password' => 'short',
        'password_confirmation' => 'short',
        'phone_number' => '+212600000000',
        'age' => 25,
        'gender' => 'male',
        'syndicate_card' => UploadedFile::fake()->create('card.pdf', 1024, 'application/pdf'),
    ]);

    $response->assertStatus(422);
});

it('logs in a registered pharmacist', function () {
    $user = User::factory()->create([
        'email' => 'login@test.ma',
        'password' => Hash::make('password123'),
    ]);
    $user->assignRole('pharmacist');
    Pharmacist::factory()->create(['user_id' => $user->id]);

    $response = $this->postJson('/api/v1/pharmacist/login', [
        'email' => 'login@test.ma',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['token']]);
});

it('rejects login with wrong password', function () {
    $user = User::factory()->create([
        'email' => 'wrong@test.ma',
        'password' => Hash::make('correct'),
    ]);
    $user->assignRole('pharmacist');
    Pharmacist::factory()->create(['user_id' => $user->id]);

    $response = $this->postJson('/api/v1/pharmacist/login', [
        'email' => 'wrong@test.ma',
        'password' => 'wrong',
    ]);

    $response->assertStatus(401);
});

it('rejects login for non-pharmacist user', function () {
    User::factory()->create([
        'email' => 'patient@test.ma',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/v1/pharmacist/login', [
        'email' => 'patient@test.ma',
        'password' => 'password123',
    ]);

    $response->assertStatus(403);
});

it('logs out the authenticated pharmacist', function () {
    extract(actingAsPharmacist());

    $response = $this->withToken($token)
        ->postJson('/api/v1/pharmacist/logout');

    $response->assertStatus(200);
});

it('rejects unauthenticated requests', function () {
    $response = $this->postJson('/api/v1/pharmacist/logout');

    $response->assertStatus(401);
});
