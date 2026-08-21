<?php

declare(strict_types=1);

use App\Http\Controllers\API\V1\Company\AdminVerificationController;
use App\Http\Controllers\API\V1\Company\AssignmentController;
use App\Http\Controllers\API\V1\Company\AuthController;
use App\Http\Controllers\API\V1\Company\RepController;
use App\Http\Controllers\API\V1\Company\ScheduleController;
use App\Http\Controllers\API\V1\Company\VisitController;
use App\Http\Controllers\API\V1\TwoFactorController;
use Illuminate\Support\Facades\Route;

Route::prefix('company')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:otp-request');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::post('two-factor/verify', [TwoFactorController::class, 'verify'])->middleware('throttle:login');

    Route::middleware('auth:sanctum')->group(function () {

        Route::middleware('role:company_owner')->group(function () {
            Route::get('dashboard', [AuthController::class, 'dashboard']);
            Route::get('profile', [AuthController::class, 'profile']);
            Route::put('profile', [AuthController::class, 'updateProfile']);
            Route::post('logout', [AuthController::class, 'logout']);

            // Two-Factor Authentication
            Route::prefix('two-factor')->group(function () {
                Route::get('status', [TwoFactorController::class, 'status']);
                Route::post('enable', [TwoFactorController::class, 'enable']);
                Route::post('confirm', [TwoFactorController::class, 'confirm']);
                Route::post('disable', [TwoFactorController::class, 'disable']);
                Route::get('recovery-codes', [TwoFactorController::class, 'recoveryCodes']);
            });

            Route::prefix('reps')->group(function () {
                Route::get('/', [RepController::class, 'index']);
                Route::post('/', [RepController::class, 'store']);
                Route::get('{rep}', [RepController::class, 'show']);
                Route::post('{rep}/suspend', [RepController::class, 'suspend']);
                Route::post('{rep}/activate', [RepController::class, 'activate']);
                Route::delete('{rep}', [RepController::class, 'destroy']);
            });

            Route::prefix('assignments')->group(function () {
                Route::get('/', [AssignmentController::class, 'index']);
                Route::post('/', [AssignmentController::class, 'store']);
                Route::delete('{assignment}', [AssignmentController::class, 'destroy']);
            });

            Route::prefix('schedules')->group(function () {
                Route::get('/', [ScheduleController::class, 'index']);
                Route::post('/', [ScheduleController::class, 'store']);
                Route::post('batch', [ScheduleController::class, 'batch']);
                Route::post('{schedule}/publish', [ScheduleController::class, 'publish']);
                Route::post('{schedule}/cancel', [ScheduleController::class, 'cancel']);
            });

            Route::prefix('visits')->group(function () {
                Route::get('/', [VisitController::class, 'index']);
                Route::get('export', [VisitController::class, 'export']);
                Route::get('stats', [VisitController::class, 'stats']);
                Route::get('{visit}', [VisitController::class, 'show']);
            });
        });
    });
});

Route::prefix('company/admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('pending', [AdminVerificationController::class, 'pending']);
    Route::post('{company}/verify', [AdminVerificationController::class, 'verify']);
});
