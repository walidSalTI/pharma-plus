<?php

declare(strict_types=1);

use App\Models\Doctor;
use App\Models\User;
use App\Models\WeeklySchedule;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::updateOrCreate(['name' => 'scientific_rep', 'guard_name' => 'api']);
    Role::updateOrCreate(['name' => 'company_owner', 'guard_name' => 'api']);
});

it('lists rep schedules', function () {
    extract(actingAsScientificRep());
    $doctor = Doctor::create(['user_id' => User::factory()->create()->id, 'specialization' => 'General']);
    WeeklySchedule::create([
        'rep_id' => $rep->id,
        'doctor_id' => $doctor->id,
        'scheduled_at' => now()->addDay(),
        'status' => 'upcoming',
        'is_reminded' => false,
    ]);

    $response = $this->withToken($token)
        ->getJson('/api/v1/rep/schedules');

    $response->assertStatus(200)
        ->assertJsonStructure(['data']);
});

it('shows a single schedule', function () {
    extract(actingAsScientificRep());
    $doctor = Doctor::create(['user_id' => User::factory()->create()->id, 'specialization' => 'General']);
    $schedule = WeeklySchedule::create([
        'rep_id' => $rep->id,
        'doctor_id' => $doctor->id,
        'scheduled_at' => now()->addDay(),
        'status' => 'upcoming',
        'is_reminded' => false,
    ]);

    $response = $this->withToken($token)
        ->getJson('/api/v1/rep/schedules/'.$schedule->id);

    $response->assertStatus(200);
});
