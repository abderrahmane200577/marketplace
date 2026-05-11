<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Multi-Vendor Marketplace
|--------------------------------------------------------------------------
|
| Prefix:     /api
| Auth:       Laravel Sanctum (token-based)
| Middleware: auth:sanctum → role:xxx → vendor.approved (where needed)
|
*/

// ─── Public Routes (no auth required) ─────────────────────────────────────
Route::prefix('auth')->group(function () {

    Route::post('/register',           [AuthController::class, 'register']);
    Route::post('/login',              [AuthController::class, 'login']);
    Route::get('/verify-email/{token}',[AuthController::class, 'verifyEmail']);
    Route::post('/forgot-password',    [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password',     [AuthController::class, 'resetPassword']);

});

// ─── Authenticated Routes ──────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout',              [AuthController::class, 'logout']);
        Route::get('/me',                   [AuthController::class, 'me']);
        Route::post('/resend-verification', [AuthController::class, 'resendVerification']);
    });

    // ── Customer Routes ────────────────────────────────────────────
    Route::middleware('role:customer,admin')->group(function () {
        // Cart, orders, profile → will be added in Week 3
        Route::get('/customer/dashboard', fn() => response()->json(['message' => 'Customer area — coming in Week 3']));
    });

    // ── Vendor Routes ──────────────────────────────────────────────
    Route::middleware(['role:vendor', 'vendor.approved'])->prefix('vendor')->group(function () {
        // Products, inventory, orders → will be added in Week 2
        Route::get('/dashboard', fn() => response()->json(['message' => 'Vendor area — coming in Week 2']));
    });

    // ── Admin Routes ───────────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // User management, vendor approval → will be added in Week 4
        Route::get('/dashboard', fn() => response()->json(['message' => 'Admin area — coming in Week 4']));
    });

});

// ─── Health Check ──────────────────────────────────────────────────────────
Route::get('/health', fn() => response()->json([
    'status'  => 'ok',
    'service' => 'Multi-Vendor Marketplace API',
    'version' => '1.0.0',
]));
