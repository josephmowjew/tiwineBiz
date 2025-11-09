<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Authentication Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        // Public routes
        Route::post('/register', [AuthController::class, 'register'])->name('api.v1.auth.register');
        Route::post('/login', [AuthController::class, 'login'])->name('api.v1.auth.login');

        // Protected routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
            Route::get('/user', [AuthController::class, 'user'])->name('api.v1.auth.user');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Protected API Routes
    |--------------------------------------------------------------------------
    | All routes below require authentication via Sanctum
    */
    Route::middleware('auth:sanctum')->group(function () {

        // Shop Management
        Route::apiResource('shops', \App\Http\Controllers\Api\V1\ShopController::class);

        // Product Management
        Route::apiResource('products', \App\Http\Controllers\Api\V1\ProductController::class);

        // Customer Management
        Route::apiResource('customers', \App\Http\Controllers\Api\V1\CustomerController::class);

        // Supplier Management
        Route::apiResource('suppliers', \App\Http\Controllers\Api\V1\SupplierController::class);

        // Category Management
        Route::apiResource('categories', \App\Http\Controllers\Api\V1\CategoryController::class);

        // Sales Management
        Route::apiResource('sales', \App\Http\Controllers\Api\V1\SaleController::class);

        // Payment Management (Immutable - no updates or deletes)
        Route::apiResource('payments', \App\Http\Controllers\Api\V1\PaymentController::class)->only(['index', 'store', 'show']);

        // Credit Management (Customer layaway/credit purchases)
        Route::apiResource('credits', \App\Http\Controllers\Api\V1\CreditController::class);

    });
});
