<?php

declare(strict_types=1);

use App\Http\Controllers\API\V1\Specialist\AuthController;
use App\Http\Controllers\API\V1\Specialist\KnowledgeBaseController;
use App\Http\Controllers\API\V1\Specialist\MedicationVerificationController;
use App\Http\Controllers\API\V1\Specialist\ProposalController;
use App\Http\Controllers\API\V1\Specialist\RiskMappingController;
use Illuminate\Support\Facades\Route;

Route::prefix('specialist')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware(['auth:sanctum', 'role:specialist'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('dashboard', [AuthController::class, 'dashboard']);

        Route::prefix('proposals')->group(function () {
            Route::get('/', [ProposalController::class, 'index']);
            Route::get('{proposal}', [ProposalController::class, 'show']);
            Route::post('{proposal}/approve', [ProposalController::class, 'approve']);
            Route::post('{proposal}/reject', [ProposalController::class, 'reject']);
        });

        // Medication Verification
        Route::get('medication-verifications', [MedicationVerificationController::class, 'index']);
        Route::post('medication-verifications/{medication}/accept', [MedicationVerificationController::class, 'accept']);
        Route::post('medication-verifications/{medication}/reject', [MedicationVerificationController::class, 'reject']);

        Route::prefix('risk-mappings')->group(function () {
            Route::get('/', [RiskMappingController::class, 'index']);
            Route::post('/', [RiskMappingController::class, 'store']);
            Route::put('{mapping}', [RiskMappingController::class, 'update']);
            Route::delete('{mapping}', [RiskMappingController::class, 'destroy']);
            Route::get('chronic-diseases', [RiskMappingController::class, 'chronicDiseases']);
            Route::get('active-ingredients', [RiskMappingController::class, 'activeIngredients']);
        });

        Route::prefix('knowledge-base')->group(function () {
            Route::get('chronic-diseases', [KnowledgeBaseController::class, 'chronicDiseases']);
            Route::post('chronic-diseases', [KnowledgeBaseController::class, 'storeChronicDisease']);
            Route::put('chronic-diseases/{chronicDisease}', [KnowledgeBaseController::class, 'updateChronicDisease']);
            Route::delete('chronic-diseases/{chronicDisease}', [KnowledgeBaseController::class, 'destroyChronicDisease']);

            Route::get('active-ingredients', [KnowledgeBaseController::class, 'activeIngredients']);
            Route::post('active-ingredients', [KnowledgeBaseController::class, 'storeActiveIngredient']);
            Route::put('active-ingredients/{activeIngredient}', [KnowledgeBaseController::class, 'updateActiveIngredient']);
            Route::delete('active-ingredients/{activeIngredient}', [KnowledgeBaseController::class, 'destroyActiveIngredient']);
        });
    });
});
