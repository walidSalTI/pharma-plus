<?php

declare(strict_types=1);

use App\Http\Controllers\API\V1\Pharmacy\AuthController;
use App\Http\Controllers\API\V1\Pharmacy\BatchController;
use App\Http\Controllers\API\V1\Pharmacy\DemandController;
use App\Http\Controllers\API\V1\Pharmacy\ExpenseController;
use App\Http\Controllers\API\V1\Pharmacy\ForecastController;
use App\Http\Controllers\API\V1\Pharmacy\InventoryController;
use App\Http\Controllers\API\V1\Pharmacy\MedicationController;
use App\Http\Controllers\API\V1\Pharmacy\NotificationController;
use App\Http\Controllers\API\V1\Pharmacy\OperatingHourController;
use App\Http\Controllers\API\V1\Pharmacy\OrderController;
use App\Http\Controllers\API\V1\Pharmacy\PosController;
use App\Http\Controllers\API\V1\Pharmacy\ProductController;
use App\Http\Controllers\API\V1\Pharmacy\ProfileController;
use App\Http\Controllers\API\V1\Pharmacy\ProposalController;
use App\Http\Controllers\API\V1\Pharmacy\ReportController;
use App\Http\Controllers\API\V1\Pharmacy\ReviewController;
use App\Http\Controllers\API\V1\Pharmacy\SalaryController;
use App\Http\Controllers\API\V1\Pharmacy\StaffController;
use App\Http\Controllers\API\V1\TwoFactorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pharmacist / Pharmacy API Routes (FR-PH)
|--------------------------------------------------------------------------
|
| Prefix: /api/v1/pharmacist
| Middleware: api
|
| These routes implement the complete Pharmacist functional requirements
| covering authentication, inventory, demand tracking, disease forecasting,
| catalog contributions, profile management, reviews, and reporting.
|
*/

Route::prefix('pharmacist')->group(function () {

    // ─── Authentication (FR-PH-1) — no auth ───────────────────────────
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::post('two-factor/verify', [TwoFactorController::class, 'verify']);

    // ─── Authenticated Routes ──────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'role:pharmacist'])->group(function () {

        // Auth
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('dashboard', [AuthController::class, 'dashboard']);

        // Two-Factor Authentication
        Route::prefix('two-factor')->group(function () {
            Route::get('status', [TwoFactorController::class, 'status']);
            Route::post('enable', [TwoFactorController::class, 'enable']);
            Route::post('confirm', [TwoFactorController::class, 'confirm']);
            Route::post('disable', [TwoFactorController::class, 'disable']);
            Route::get('recovery-codes', [TwoFactorController::class, 'recoveryCodes']);
        });

        // Verification (FR-PH-1.2)
        Route::post('verify', [AuthController::class, 'submitVerification']);
        Route::get('verification-status', [AuthController::class, 'verificationStatus']);

        // Profile (FR-PH-6.1) — user profile (not pharmacy-scoped)
        Route::get('profile', [ProfileController::class, 'showProfile']);
        Route::put('update-profile', [ProfileController::class, 'updateProfile']);

        // Pharmacy management
        Route::post('pharmacy', [ProfileController::class, 'storePharmacy']);
        Route::get('pharmacies/search', [ProfileController::class, 'searchPharmacies']);
        Route::get('pharmacies/{pharmacy}', [ProfileController::class, 'showPharmacy']);

        // ─── Pharmacy-scoped routes (require pharmacy_id) ─────────────
        Route::prefix('pharmacies/{pharmacy}')->group(function () {

            // Pharmacy Profile (FR-PH-6.1)
            Route::get('profile', [ProfileController::class, 'show']);
            Route::put('profile', [ProfileController::class, 'update']);

            // Unverified Medication Creation (FR-PH-2)
            Route::get('medications', [MedicationController::class, 'index']);
            Route::post('medications', [MedicationController::class, 'store']);
            Route::get('medications/{medication}', [MedicationController::class, 'show']);

            // Products (non-medication pharmacy products)
            Route::get('products', [ProductController::class, 'index']);
            Route::post('products', [ProductController::class, 'store']);
            Route::get('products/{product}', [ProductController::class, 'show']);
            Route::put('products/{product}', [ProductController::class, 'update']);
            Route::delete('products/{product}', [ProductController::class, 'destroy']);

            // Inventory (FR-PH-2)
            Route::prefix('inventory')->group(function () {
                Route::get('/', [InventoryController::class, 'index']);
                Route::post('/', [InventoryController::class, 'store']);
                Route::put('/', [InventoryController::class, 'update']);
                Route::post('bulk-import', [InventoryController::class, 'bulkImport']);
                Route::get('low-stock', [InventoryController::class, 'lowStock']);
                Route::get('export', [ReportController::class, 'export']);
                Route::put('{inventory}', [InventoryController::class, 'updateSingle']);
                // Route::patch('{inventory}/increment', [InventoryController::class, 'incrementStock']);
                // Route::patch('{inventory}/decrement', [InventoryController::class, 'decrementStock']);
                Route::delete('{inventory}', [InventoryController::class, 'destroy'])->missing(fn () => response()->json(['message' => 'Inventory item not found.'], 404));

                // Batch Management (FEFO)
                Route::prefix('{inventory}/batches')->group(function () {
                    Route::get('/', [BatchController::class, 'index']);
                    Route::post('/', [BatchController::class, 'store']);
                    Route::get('{batch}', [BatchController::class, 'show']);
                    Route::put('{batch}', [BatchController::class, 'update']);
                    Route::delete('{batch}', [BatchController::class, 'destroy']);
                });
            });

            // Operating Hours (FR-PH-6.1, FR-PH-6.2)
            Route::get('operating-hours', [OperatingHourController::class, 'index']);
            Route::put('operating-hours', [OperatingHourController::class, 'upsert']);
            Route::post('vacation', [OperatingHourController::class, 'declareVacation']);

            // Regional Demand (FR-PH-3)
            Route::get('demand-map', [DemandController::class, 'demandMap']);

            // Disease Forecasting (FR-PH-4)
            Route::get('disease-forecasts', [ForecastController::class, 'forecasts']);

            // Staff (FR-PH-6.3)
            Route::prefix('staff')->group(function () {
                Route::post('search', [StaffController::class, 'search']);
                Route::post('invite/{targetPharmacist}', [StaffController::class, 'invite']);
                Route::get('/', [StaffController::class, 'index']);
                Route::post('/', [StaffController::class, 'store']);
                Route::put('{staff}', [StaffController::class, 'update']);
                Route::delete('{staff}', [StaffController::class, 'destroy']);
            });

            // Join Request (FR-PH-6.3) — pharmacist requests to join
            Route::post('join-request', [NotificationController::class, 'sendJoinRequest']);

            // Permissions (FR-PH-6.3) — current pharmacist's permissions
            Route::get('permissions', [StaffController::class, 'getPermissions']);

            // Reviews (FR-PH-7)
            Route::get('reviews', [ReviewController::class, 'index']);
            Route::post('reviews/{review}/reply', [ReviewController::class, 'reply']);

            // Reports (FR-PH-8)
            Route::get('reports/financial-summary', [ReportController::class, 'financialSummary']);

            // Orders (FR-PH-2.3) — list, filter & status lifecycle
            Route::prefix('orders')->group(function () {
                Route::get('/', [OrderController::class, 'index']);
                Route::patch('{order}/status', [OrderController::class, 'updateStatus']);
            });

            // POS (Point of Sale) — walk-in customer sales without app
            Route::prefix('pos')->group(function () {
                Route::get('find-item', [PosController::class, 'findItem']);
                Route::post('checkout', [PosController::class, 'store']);
                Route::post('purchase', [PosController::class, 'purchase']);
                Route::post('damaged', [PosController::class, 'recordDamaged']);
                Route::post('reverse-damage', [PosController::class, 'reverseDamage']);
                Route::post('return', [PosController::class, 'returnSale']);
            });

            // Expenses — pharmacy operational cost tracking
            Route::prefix('expenses')->group(function () {
                Route::get('/', [ExpenseController::class, 'index']);
                Route::post('/', [ExpenseController::class, 'store']);
                Route::get('{expense}', [ExpenseController::class, 'show']);
                Route::put('{expense}', [ExpenseController::class, 'update']);
                Route::delete('{expense}', [ExpenseController::class, 'destroy']);
            });

            // Salaries — pharmacist/staff salary payment tracking
            Route::prefix('salaries')->group(function () {
                Route::get('/', [SalaryController::class, 'index']);
                Route::post('/', [SalaryController::class, 'store']);
                Route::get('{salary}', [SalaryController::class, 'show']);
                Route::put('{salary}', [SalaryController::class, 'update']);
                Route::delete('{salary}', [SalaryController::class, 'destroy']);
            });

        });

        // Proposals (FR-PH-5) — not pharmacy-scoped
        Route::prefix('proposals')->group(function () {
            Route::get('/', [ProposalController::class, 'index']);
            Route::post('/', [ProposalController::class, 'store']);
            Route::get('{proposal}', [ProposalController::class, 'show']);
        });

        // Notifications (FR-PH-6.3) — not pharmacy-scoped
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::post('{notification}/accept-invitation', [NotificationController::class, 'acceptStaffInvitation']);
            Route::post('{notification}/reject-invitation', [NotificationController::class, 'rejectStaffInvitation']);
            Route::post('{notification}/accept-join-request', [NotificationController::class, 'acceptJoinRequest']);
            Route::post('{notification}/reject-join-request', [NotificationController::class, 'rejectJoinRequest']);
        });

    });
});
