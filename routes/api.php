<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\WishlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// ==================== HEALTH CHECK ROUTES ====================
Route::prefix('health')->group(function () {
    Route::get('/', [HealthController::class, 'index']);
    Route::get('/detailed', [HealthController::class, 'detailed']);
    Route::get('/liveness', [HealthController::class, 'live']);
    Route::get('/readiness', [HealthController::class, 'ready']);
});

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    // ==================== AUTH ROUTES ====================
    Route::prefix('auth')->group(function () {
        // Public routes
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail']);
        Route::post('/resend-verification', [AuthController::class, 'resendVerification']);

        // Protected routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        });
    });

    // ==================== CATEGORY ROUTES ====================
    // Public category routes
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    Route::get('/categories/slug/{slug}', [CategoryController::class, 'showBySlug']);

    // ==================== PRODUCT ROUTES ====================
    // Public product routes
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::get('/products/slug/{slug}', [ProductController::class, 'showBySlug']);

    // Protected product routes (requires authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::patch('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    });

    // ==================== ORDER ROUTES ====================
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/orders', [OrderController::class, 'index']);
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    });

    // ==================== WISHLIST ROUTES ====================
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/wishlist', [WishlistController::class, 'index']);
        Route::post('/wishlist', [WishlistController::class, 'store']);
        Route::delete('/wishlist/{product}', [WishlistController::class, 'destroy']);
        Route::post('/wishlist/toggle', [WishlistController::class, 'toggle']);
        Route::get('/wishlist/check/{product}', [WishlistController::class, 'check']);
        Route::delete('/wishlist', [WishlistController::class, 'clear']);
        Route::get('/wishlist/count', [WishlistController::class, 'count']);
    });

    // ==================== PAYMENT ROUTES ====================
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/payments/initiate', [PaymentController::class, 'initiate']);
        Route::get('/payments/{merchantOid}/status', [PaymentController::class, 'status']);
    });

    // PayTR webhook (no auth required)
    Route::post('/payments/notify', [PaymentController::class, 'notify']);
});
