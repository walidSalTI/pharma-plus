<?php

declare(strict_types=1);

use App\Models\Doctor;
use App\Models\DoctorWorkplace;
use App\Models\PharmaceuticalCompany;
use App\Models\Pharmacist;
use App\Models\Pharmacy;
use App\Models\ScientificRep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', fn () => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create an authenticated pharmacist with a linked pharmacy.
 *
 * @return array{user: User, pharmacist: Pharmacist, pharmacy: Pharmacy, token: string}
 */
function actingAsPharmacist(): array
{
    $user = User::factory()->create();
    $user->assignRole('pharmacist');
    $pharmacist = Pharmacist::factory()->create(['user_id' => $user->id]);
    $pharmacy = Pharmacy::factory()->create(['pharmacist_id' => $pharmacist->id]);
    $token = $user->createToken('test')->plainTextToken;

    return ['user' => $user, 'pharmacist' => $pharmacist, 'pharmacy' => $pharmacy, 'token' => $token];
}

/**
 * Create an authenticated doctor with workplaces.
 *
 * @return array{user: User, doctor: Doctor, workplace: DoctorWorkplace, token: string}
 */
function actingAsDoctor(): array
{
    $user = User::factory()->create();
    Role::updateOrCreate(['name' => 'doctor', 'guard_name' => 'api']);
    $user->assignRole('doctor');
    $doctor = Doctor::create([
        'user_id' => $user->id,
        'specialization' => 'Cardiology',
        'syndicate_card_image' => null,
        'doctor_secret_key' => (new Google2FA)->generateSecretKey(),
        'verification_status' => 'approved',
    ]);
    $workplace = DoctorWorkplace::create([
        'doctor_id' => $doctor->id,
        'place_name' => 'Test Clinic',
        'place_type' => 'clinic',
        'latitude' => 30.0444,
        'longitude' => 31.2357,
        'radius_meters' => 50,
    ]);
    $token = $user->createToken('test')->plainTextToken;

    return ['user' => $user, 'doctor' => $doctor, 'workplace' => $workplace, 'token' => $token];
}

/**
 * Create an authenticated company owner with a company.
 *
 * @return array{user: User, company: PharmaceuticalCompany, token: string}
 */
function actingAsCompanyOwner(): array
{
    $user = User::factory()->create();
    Role::updateOrCreate(['name' => 'company_owner', 'guard_name' => 'api']);
    $user->assignRole('company_owner');
    $company = PharmaceuticalCompany::create([
        'owner_id' => $user->id,
        'commercial_name' => 'Pharma Corp',
        'commercial_registration' => 'CR-'.str()->random(8),
        'address' => '123 Business St',
        'phone' => '+201234567890',
        'license_number' => 'LIC-'.str()->random(8),
        'license_image' => 'company_licenses/test.jpg',
        'status' => 'active',
    ]);
    $token = $user->createToken('test')->plainTextToken;

    return ['user' => $user, 'company' => $company, 'token' => $token];
}

/**
 * Create an authenticated scientific rep linked to a company.
 *
 * @return array{user: User, rep: ScientificRep, company: PharmaceuticalCompany, token: string}
 */
function actingAsScientificRep(): array
{
    $owner = User::factory()->create();
    Role::updateOrCreate(['name' => 'company_owner', 'guard_name' => 'api']);
    Role::updateOrCreate(['name' => 'scientific_rep', 'guard_name' => 'api']);
    $owner->assignRole('company_owner');
    $company = PharmaceuticalCompany::create([
        'owner_id' => $owner->id,
        'commercial_name' => 'Pharma Rep Corp',
        'commercial_registration' => 'CR-'.str()->random(8),
        'address' => '456 Rep St',
        'phone' => '+201234567891',
        'license_number' => 'LIC-'.str()->random(8),
        'license_image' => 'company_licenses/test.jpg',
        'status' => 'active',
    ]);
    $user = User::factory()->create();
    $user->assignRole('scientific_rep');
    $rep = ScientificRep::create(['user_id' => $user->id, 'company_id' => $company->id]);
    $token = $user->createToken('test')->plainTextToken;

    return ['user' => $user, 'rep' => $rep, 'company' => $company, 'token' => $token];
}
