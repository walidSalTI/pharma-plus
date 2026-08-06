<?php

declare(strict_types=1);

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::updateOrCreate(['name' => 'doctor', 'guard_name' => 'api']);
});

it('registers a new doctor', function () {
    $response = $this->postJson('/api/v1/doctor/register', [
        'f_name' => 'Khaled',
        'l_name' => 'Ahmed',
        'email' => 'dr.khaled@example.com',
        'password' => 'securepass123',
        'password_confirmation' => 'securepass123',
        'phone_number' => '01000000000',
        'age' => 45,
        'gender' => 'male',
        'specialization' => 'Cardiology',
        'syndicate_card_image' => UploadedFile::fake()->create('card.jpg', 1024, 'image/jpeg'),
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['message', 'data' => ['doctor']])
        ->assertJsonMissing(['data' => ['token']]);

    expect(User::where('email', 'dr.khaled@example.com')->exists())->toBeTrue();
});

it('rejects doctor registration with duplicate email', function () {
    User::factory()->create(['email' => 'dup@test.com']);

    $response = $this->postJson('/api/v1/doctor/register', [
        'f_name' => 'Test',
        'l_name' => 'User',
        'email' => 'dup@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone_number' => '01000000000',
        'age' => 30,
        'gender' => 'male',
        'specialization' => 'General',
    ]);

    $response->assertStatus(422);
});

it('logs in a registered doctor', function () {
    $user = User::factory()->create([
        'email' => 'login@doctor.com',
        'password' => Hash::make('password123'),
    ]);
    $user->assignRole('doctor');
    Doctor::create(['user_id' => $user->id, 'specialization' => 'Cardiology']);

    $response = $this->postJson('/api/v1/doctor/login', [
        'email' => 'login@doctor.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['token']]);
});

it('rejects doctor login with wrong credentials', function () {
    User::factory()->create([
        'email' => 'wrong@doctor.com',
        'password' => Hash::make('correct'),
    ]);

    $response = $this->postJson('/api/v1/doctor/login', [
        'email' => 'wrong@doctor.com',
        'password' => 'wrongpass',
    ]);

    $response->assertStatus(401);
});

it('rejects login for non-doctor user', function () {
    User::factory()->create([
        'email' => 'patient@test.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/v1/doctor/login', [
        'email' => 'patient@test.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(403);
});

it('logs out the authenticated doctor', function () {
    extract(actingAsDoctor());

    $response = $this->withToken($token)
        ->postJson('/api/v1/doctor/logout');

    $response->assertStatus(200);
});

it('returns doctor profile', function () {
    extract(actingAsDoctor());

    $response = $this->withToken($token)
        ->getJson('/api/v1/doctor/profile');

    $response->assertStatus(200)
        ->assertJsonStructure(['data']);
});

it('rejects unauthenticated access', function () {
    $response = $this->getJson('/api/v1/doctor/profile');

    $response->assertStatus(401);
});
