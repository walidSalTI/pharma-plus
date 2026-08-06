<?php

declare(strict_types=1);

use App\Http\Controllers\API\V1\Doctor\AuthController;
use App\Http\Controllers\API\V1\Doctor\DoctorController;
use App\Http\Controllers\API\V1\Doctor\QrController;
use App\Http\Controllers\API\V1\Doctor\WorkplaceController;
use App\Http\Controllers\API\V1\TwoFactorController;
use Illuminate\Support\Facades\Route;

Route::prefix('doctor')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::post('two-factor/verify', [TwoFactorController::class, 'verify']);

    Route::get('list', [DoctorController::class, 'index']);

    Route::middleware(['auth:sanctum', 'role:doctor'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('profile', [AuthController::class, 'profile']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::post('submit-verification', [AuthController::class, 'submitVerification']);

        Route::prefix('two-factor')->group(function () {
            Route::get('status', [TwoFactorController::class, 'status']);
            Route::post('enable', [TwoFactorController::class, 'enable']);
            Route::post('confirm', [TwoFactorController::class, 'confirm']);
            Route::post('disable', [TwoFactorController::class, 'disable']);
            Route::get('recovery-codes', [TwoFactorController::class, 'recoveryCodes']);
        });

        Route::middleware('doctor.verified')->group(function () {
            Route::get('qr/secret-key', [QrController::class, 'getSecretKey']);

            Route::prefix('workplaces')->group(function () {
                Route::get('/', [WorkplaceController::class, 'index']);
                Route::post('/', [WorkplaceController::class, 'store']);
                Route::put('{workplace}', [WorkplaceController::class, 'update']);
                Route::delete('{workplace}', [WorkplaceController::class, 'destroy']);
            });
        });
    });
});
