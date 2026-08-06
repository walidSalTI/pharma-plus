<?php

declare(strict_types=1);

use App\Http\Controllers\API\V1\Medication\CategoryController;
use App\Http\Controllers\API\V1\Medication\MedicationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Global Medication Catalog API Routes (FR-P-3)
|--------------------------------------------------------------------------
|
| Prefix: /api/v1
| Middleware: api
|
| These public endpoints provide access to the global medication catalog
| and its category hierarchy for all user roles.
|
*/

// Medication catalog
Route::get('medications', [MedicationController::class, 'index']);

// Category hierarchy cascading dropdowns
Route::get('categories', [CategoryController::class, 'index']);
Route::get('categories/{category}/titles', [CategoryController::class, 'titles']);
Route::get('titles/{title}/usages', [CategoryController::class, 'usages']);
