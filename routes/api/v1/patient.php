<?php

declare(strict_types=1);

use App\Http\Controllers\API\V1\Patient\AccountDashboardController;
use App\Http\Controllers\API\V1\Patient\AuthController;
use App\Http\Controllers\API\V1\Patient\ChronicDiseaseController;
use App\Http\Controllers\API\V1\Patient\MedicationLogController;
use App\Http\Controllers\API\V1\Patient\MedicationWalletController;
use App\Http\Controllers\API\V1\Patient\OrderController;
use App\Http\Controllers\API\V1\Patient\PharmacyReviewController;
use App\Http\Controllers\API\V1\Patient\ProfileController;
use App\Http\Controllers\API\V1\Patient\SearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Patient Module API Routes (FR-P-1 through FR-P-9)
|--------------------------------------------------------------------------
|
| Prefix: /api/v1/patient
| Middleware: api
|
| These endpoints implement the complete Patient functional requirements
| covering smart drug search, safety evaluation, geospatial ranking,
| real-time ordering, account management, chronic disease tracking,
| medication wallet, smart pillbox scheduling, and intake logging.
|
*/

Route::prefix('patient')->group(function () {

    // ─── Public Routes (no auth required) ─────────────────────────────────
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:otp-request');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('search', SearchController::class)->middleware('throttle:public-search');
    Route::post('search/precheck', [SearchController::class, 'precheck'])->middleware('throttle:public-search');

    // ─── Authenticated Patient Routes ─────────────────────────────────────
    Route::middleware(['auth:sanctum', 'role:patient'])->group(function () {

        // Authentication
        Route::post('logout', [AuthController::class, 'logout']);

        // ─── Patient Profile (FR-P-1.3, FR-P-2.4) ──────────────────────────
        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);

        // Account & Data Portability (FR-P-1.4)
        Route::post('ledger/export', [AccountDashboardController::class, 'exportLedger']);
        Route::delete('account', [AccountDashboardController::class, 'destroyAccount']);

        // ─── Chronic Disease Management (FR-P-2.1, FR-P-2.4) ─────────────────
        Route::prefix('diseases')->group(function () {
            Route::get('/', [ChronicDiseaseController::class, 'index']);
            Route::post('/', [ChronicDiseaseController::class, 'store']);
            Route::put('{chronicRecord}', [ChronicDiseaseController::class, 'update']);
            Route::delete('{chronicRecord}', [ChronicDiseaseController::class, 'destroy']);
        });

        // ─── Medication Wallet & Scheduling (FR-P-2.3, FR-P-2.4, FR-P-8.1) ──
        Route::prefix('wallet')->group(function () {
            Route::get('/', [MedicationWalletController::class, 'index']);
            Route::post('/', [MedicationWalletController::class, 'store']);
            Route::put('{medicationPatient}', [MedicationWalletController::class, 'update']);
            Route::delete('{medicationPatient}', [MedicationWalletController::class, 'destroy']);
            Route::patch('{medicationPatient}/toggle', [MedicationWalletController::class, 'toggle']);
            Route::patch('{medicationPatient}/pills', [MedicationWalletController::class, 'patchPills']);
        });

        // ─── Intake Logging & Smart Cabinet (FR-P-8.3, FR-P-8.4, FR-P-8.5) ──
        Route::prefix('logs')->group(function () {
            Route::get('/', [MedicationLogController::class, 'index']);
            Route::post('/', [MedicationLogController::class, 'store']);
        });

        // ─── Medication Ordering (FR-P-6.3) ─────────────────────────────────
        Route::prefix('orders')->group(function () {
            Route::post('hold', [OrderController::class, 'holdMedication']);
            Route::get('unreviewed', [PharmacyReviewController::class, 'unreviewedOrders']);
            Route::get('unreviewed/{pharmacyId}', [PharmacyReviewController::class, 'unreviewedOrders']);
        });

        // ─── Pharmacy Reviews (FR-P-7) ─────────────────────────────────────
        Route::post('reviews/{pharmacy}', [PharmacyReviewController::class, 'store']);

    });

});
