<?php

declare(strict_types=1);

use App\Http\Controllers\API\V1\Disease\DiseaseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Global Chronic Disease Catalog API Routes
|--------------------------------------------------------------------------
|
| Prefix: /api/v1/chronic-diseases
| Middleware: api
|
| Public read endpoints (no auth required):
|   GET  /api/v1/chronic-diseases           — List & filter diseases
|   GET  /api/v1/chronic-diseases/{disease} — Show single disease
|
| Admin-only endpoints (auth:sanctum + role:admin):
|   POST   /api/v1/chronic-diseases              — Create disease
|   PUT    /api/v1/chronic-diseases/{disease}    — Update disease
|   DELETE /api/v1/chronic-diseases/{disease}    — Delete disease
|
| Note: The path is "chronic-diseases" (not "diseases") to avoid conflict
| with the patient module's personal chronic record routes at /api/v1/diseases.
| This module manages the GLOBAL chronic_diseases catalog (all diseases in the
| system), while the patient module manages individual chronic_records.
|
*/

// ─── Public Endpoints (no auth required) ────────────────────────────
Route::get('chronic-diseases', [DiseaseController::class, 'index']);
Route::get('chronic-diseases/{chronicDisease}', [DiseaseController::class, 'show']);

// ─── Admin-only Endpoints ────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('chronic-diseases', [DiseaseController::class, 'store']);
    Route::put('chronic-diseases/{chronicDisease}', [DiseaseController::class, 'update']);
    Route::delete('chronic-diseases/{chronicDisease}', [DiseaseController::class, 'destroy']);
});
