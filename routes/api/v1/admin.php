<?php

declare(strict_types=1);

use App\Http\Controllers\API\V1\Admin\AuditController;
use App\Http\Controllers\API\V1\Admin\AuthController;
use App\Http\Controllers\API\V1\Admin\CompanyController;
use App\Http\Controllers\API\V1\Admin\DashboardController;
use App\Http\Controllers\API\V1\Admin\DoctorController;
use App\Http\Controllers\API\V1\Admin\DoctorVerificationController;
use App\Http\Controllers\API\V1\Admin\MedicalDataController;
use App\Http\Controllers\API\V1\Admin\MedicationVerificationController;
use App\Http\Controllers\API\V1\Admin\PatientController;
use App\Http\Controllers\API\V1\Admin\PharmacistController;
use App\Http\Controllers\API\V1\Admin\PharmacistVerificationController;
use App\Http\Controllers\API\V1\Admin\ProposalsController;
use App\Http\Controllers\API\V1\Admin\ScientificRepController;
use App\Http\Controllers\API\V1\Admin\SpecialistController;
use App\Http\Controllers\API\V1\Admin\UserManagementController;
use Illuminate\Support\Facades\Route;

// Public admin auth routes
Route::prefix('admin')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
});

// Protected admin routes
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('dashboard', [DashboardController::class, 'index']);

    Route::prefix('verifications')->group(function () {
        Route::get('doctors', [DoctorVerificationController::class, 'pending']);
        Route::post('doctors/{doctor}/verify', [DoctorVerificationController::class, 'verify']);

        Route::get('pharmacists', [PharmacistVerificationController::class, 'pending']);
        Route::post('pharmacists/{pharmacist}/verify', [PharmacistVerificationController::class, 'verify']);
    });

    Route::prefix('users')->group(function () {
        Route::get('/', [UserManagementController::class, 'index']);
        Route::post('/', [UserManagementController::class, 'store']);
        Route::get('{user}', [UserManagementController::class, 'show']);
        Route::put('{user}', [UserManagementController::class, 'update']);
        Route::delete('{user}', [UserManagementController::class, 'destroy']);
        Route::post('{id}/restore', [UserManagementController::class, 'restore'])->whereUuid('id');
        Route::post('{user}/suspend', [UserManagementController::class, 'suspend']);
        Route::post('{user}/assign-roles', [UserManagementController::class, 'assignRoles']);
    });

    // Per-actor admin CRUD
    Route::prefix('doctors')->group(function () {
        Route::get('/', [DoctorController::class, 'index']);
        Route::post('/', [DoctorController::class, 'store']);
        Route::get('{doctor}', [DoctorController::class, 'show']);
        Route::put('{doctor}', [DoctorController::class, 'update']);
        Route::delete('{doctor}', [DoctorController::class, 'destroy']);
    });

    Route::prefix('pharmacists')->group(function () {
        Route::get('/', [PharmacistController::class, 'index']);
        Route::post('/', [PharmacistController::class, 'store']);
        Route::get('{pharmacist}', [PharmacistController::class, 'show']);
        Route::put('{pharmacist}', [PharmacistController::class, 'update']);
        Route::delete('{pharmacist}', [PharmacistController::class, 'destroy']);
    });

    Route::prefix('patients')->group(function () {
        Route::get('/', [PatientController::class, 'index']);
        Route::post('/', [PatientController::class, 'store']);
        Route::get('{patient}', [PatientController::class, 'show']);
        Route::put('{patient}', [PatientController::class, 'update']);
        Route::delete('{patient}', [PatientController::class, 'destroy']);
    });

    Route::prefix('specialists')->group(function () {
        Route::get('/', [SpecialistController::class, 'index']);
        Route::post('/', [SpecialistController::class, 'store']);
        Route::get('{specialist}', [SpecialistController::class, 'show']);
        Route::put('{specialist}', [SpecialistController::class, 'update']);
        Route::delete('{specialist}', [SpecialistController::class, 'destroy']);
    });

    Route::prefix('scientific-reps')->group(function () {
        Route::get('/', [ScientificRepController::class, 'index']);
        Route::post('/', [ScientificRepController::class, 'store']);
        Route::get('{scientificRep}', [ScientificRepController::class, 'show']);
        Route::put('{scientificRep}', [ScientificRepController::class, 'update']);
        Route::delete('{scientificRep}', [ScientificRepController::class, 'destroy']);
    });

    Route::prefix('companies')->group(function () {
        Route::get('/', [CompanyController::class, 'index']);
        Route::post('/', [CompanyController::class, 'store']);
        Route::get('{pharmaceuticalCompany}', [CompanyController::class, 'show']);
        Route::put('{pharmaceuticalCompany}', [CompanyController::class, 'update']);
        Route::delete('{pharmaceuticalCompany}', [CompanyController::class, 'destroy']);
    });

    Route::prefix('medical-data')->group(function () {
        Route::get('chronic-diseases', [MedicalDataController::class, 'chronicDiseases']);
        Route::post('chronic-diseases', [MedicalDataController::class, 'storeChronicDisease']);
        Route::put('chronic-diseases/{chronicDisease}', [MedicalDataController::class, 'updateChronicDisease']);
        Route::delete('chronic-diseases/{chronicDisease}', [MedicalDataController::class, 'destroyChronicDisease']);

        Route::get('active-ingredients', [MedicalDataController::class, 'activeIngredients']);
        Route::post('active-ingredients', [MedicalDataController::class, 'storeActiveIngredient']);
        Route::put('active-ingredients/{activeIngredient}', [MedicalDataController::class, 'updateActiveIngredient']);
        Route::delete('active-ingredients/{activeIngredient}', [MedicalDataController::class, 'destroyActiveIngredient']);

        Route::get('medications', [MedicalDataController::class, 'medications']);
        Route::post('medications', [MedicalDataController::class, 'storeMedication']);
        Route::put('medications/{medication}', [MedicalDataController::class, 'updateMedication']);
        Route::delete('medications/{medication}', [MedicalDataController::class, 'destroyMedication']);
    });

    Route::prefix('proposals')->group(function () {
        Route::get('/', [ProposalsController::class, 'index']);
        Route::get('{proposal}', [ProposalsController::class, 'show']);
        Route::post('{proposal}/assign', [ProposalsController::class, 'assign']);
        Route::post('{proposal}/approve', [ProposalsController::class, 'approve']);
        Route::post('{proposal}/reject', [ProposalsController::class, 'reject']);
    });

    // Medication Verification
    Route::get('medication-verifications', [MedicationVerificationController::class, 'index']);
    Route::post('medication-verifications/{medication}/accept', [MedicationVerificationController::class, 'accept']);
    Route::post('medication-verifications/{medication}/reject', [MedicationVerificationController::class, 'reject']);

    Route::get('audit/activity', [AuditController::class, 'activity']);
});
