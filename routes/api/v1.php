<?php

use App\Http\Controllers\API\V1\AuthController;
use App\Http\Controllers\API\V1\ProductCategoryController;
use App\Http\Controllers\API\V1\LedDisplayController;
use App\Http\Controllers\API\V1\MerchantController;
use App\Http\Controllers\API\V1\MarketBlockController;
use App\Http\Controllers\API\V1\MarketController;
use App\Http\Controllers\API\V1\PaymentReceiptController;
use App\Http\Controllers\API\V1\PlaceController;
use App\Http\Controllers\API\V1\PlaceRequestController;
use App\Http\Controllers\API\V1\ProductController;
use App\Http\Controllers\API\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Auth
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // Public
    Route::get('product-categories', [ProductCategoryController::class, 'index']);

    Route::get('markets', [MarketController::class, 'index']);
    Route::get('markets/popular', [MarketController::class, 'popular']);
    Route::get('markets/{market}', [MarketController::class, 'show']);
    Route::get('markets/{market}/statistics', [MarketController::class, 'statistics']);

    Route::get('places', [PlaceController::class, 'index']);
    Route::get('places/{place}', [PlaceController::class, 'show']);
    Route::get('markets/{market}/blocks', [MarketBlockController::class, 'index']);

    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/trending', [ProductController::class, 'trending']);
    Route::get('products/{product}', [ProductController::class, 'show']);

    Route::get('merchants', [MerchantController::class, 'index']);

    Route::get('led-displays/{market}', [LedDisplayController::class, 'show']);

    // Authenticated
    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('profile', [AuthController::class, 'profile']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::put('password', [AuthController::class, 'updatePassword']);

        Route::post('place-requests', [PlaceRequestController::class, 'store']);
        Route::get('my/place-requests', [PlaceRequestController::class, 'mine']);
        Route::post('receipts', [PaymentReceiptController::class, 'store']);
        Route::get('my/receipts', [PaymentReceiptController::class, 'mine']);

        Route::post('products', [ProductController::class, 'store']);
        Route::put('products/{product}', [ProductController::class, 'update']);
        Route::delete('products/{product}', [ProductController::class, 'destroy']);

        // Admin
        Route::middleware(['permission:manage_markets'])->group(function () {
            Route::post('markets', [MarketController::class, 'store']);
            Route::put('markets/{market}', [MarketController::class, 'update']);
            Route::delete('markets/{market}', [MarketController::class, 'destroy']);
        });

        Route::middleware(['permission:manage_places'])->group(function () {
            Route::post('markets/{market}/blocks', [MarketBlockController::class, 'store']);
            Route::put('market-blocks/{marketBlock}', [MarketBlockController::class, 'update']);
            Route::delete('market-blocks/{marketBlock}', [MarketBlockController::class, 'destroy']);

            Route::post('places', [PlaceController::class, 'store']);
            Route::put('places/{place}', [PlaceController::class, 'update']);
            Route::delete('places/{place}', [PlaceController::class, 'destroy']);
            Route::post('places/{place}/assign-chief', [PlaceController::class, 'assignChief']);

            Route::get('place-requests', [PlaceRequestController::class, 'index']);
            Route::post('place-requests/{placeRequest}/approve', [PlaceRequestController::class, 'approve']);
            Route::post('place-requests/{placeRequest}/reject', [PlaceRequestController::class, 'reject']);
        });

        Route::middleware(['permission:manage_receipts'])->group(function () {
            Route::get('receipts', [PaymentReceiptController::class, 'index']);
            Route::post('receipts/{receipt}/approve', [PaymentReceiptController::class, 'approve']);
            Route::post('receipts/{receipt}/reject', [PaymentReceiptController::class, 'reject']);
        });

        Route::middleware(['permission:manage_users'])->group(function () {
            Route::get('users', [UserController::class, 'index']);
            Route::post('users', [UserController::class, 'store']);
            Route::put('users/{user}', [UserController::class, 'update']);
            Route::delete('users/{user}', [UserController::class, 'destroy']);
        });
    });
});