<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VendorController as AdminVendorController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Product\PublicProductController;
use App\Http\Controllers\Vendor\ProductController;
use App\Http\Controllers\Vendor\VendorController;
use Illuminate\Support\Facades\Route;

// PUBLIC ROUTES

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

Route::get('/products', [PublicProductController::class, 'index']);
Route::get('/products/{id}', [PublicProductController::class, 'show']);

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'service' => 'Multi-Vendor Marketplace API',
    'version' => '1.0.0',
]));

// AUTHENTICATED ROUTES

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/resend-verification', [AuthController::class, 'resendVerification']);
    });

    Route::middleware(['role:vendor', 'vendor.approved'])
        ->prefix('vendor')
        ->group(function () {
            Route::get('/dashboard', [VendorController::class, 'dashboard']);
            Route::get('/profile', [VendorController::class, 'profile']);
            Route::put('/profile', [VendorController::class, 'updateProfile']);

            Route::get('/products', [ProductController::class, 'index']);
            Route::post('/products', [ProductController::class, 'store']);
            Route::get('/products/{id}', [ProductController::class, 'show']);
            Route::put('/products/{id}', [ProductController::class, 'update']);
            Route::delete('/products/{id}', [ProductController::class, 'destroy']);

            Route::post('/products/{id}/images', [ProductController::class, 'addImages']);
            Route::delete('/products/{id}/images/{imageId}', [ProductController::class, 'deleteImage']);
        });

    Route::middleware('role:customer,admin')
        ->prefix('customer')
        ->group(function () {
            Route::get('/dashboard', fn () => response()->json([
                'message' => 'Customer area - coming in Week 3',
            ]));
        });

    Route::middleware('role:admin')
        ->prefix('admin')
        ->group(function () {
            Route::get('/dashboard', [UserController::class, 'dashboard']);

            Route::get('/users', [UserController::class, 'index']);
            Route::get('/users/{id}', [UserController::class, 'show']);
            Route::patch('/users/{id}/toggle-active', [UserController::class, 'toggleActive']);

            Route::get('/vendors', [AdminVendorController::class, 'index']);
            Route::get('/vendors/{id}', [AdminVendorController::class, 'show']);
            Route::patch('/vendors/{id}/approve', [AdminVendorController::class, 'approve']);
            Route::patch('/vendors/{id}/reject', [AdminVendorController::class, 'reject']);
            Route::patch('/vendors/{id}/suspend', [AdminVendorController::class, 'suspend']);
            Route::patch('/vendors/{id}/reactivate', [AdminVendorController::class, 'reactivate']);

            Route::post('/categories', [CategoryController::class, 'store']);
            Route::put('/categories/{id}', [CategoryController::class, 'update']);
            Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
        });
});
