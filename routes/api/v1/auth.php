<?php

declare(strict_types=1);

use App\Http\Controllers\API\V1\Auth\EmailVerificationController;
use App\Http\Controllers\API\V1\Auth\ForgotPasswordController;
use Illuminate\Support\Facades\Route;

Route::post('auth/send-verification-email', [EmailVerificationController::class, 'sendVerificationEmail']);
Route::post('auth/verify-email', [EmailVerificationController::class, 'verifyEmail']);
Route::post('auth/forgot-password', [ForgotPasswordController::class, 'forgotPassword']);
Route::post('auth/reset-password', [ForgotPasswordController::class, 'resetPassword']);
