<?php

use App\Http\Controllers\API\V1\AuthController;
use App\Http\Controllers\API\V1\Commerce\CashRegisterController;
use App\Http\Controllers\API\V1\Commerce\CashSessionController;
use App\Http\Controllers\API\V1\Commerce\CommerceController;
use App\Http\Controllers\API\V1\Commerce\CommerceDashboardController;
use App\Http\Controllers\API\V1\Commerce\CommerceMeController;
use App\Http\Controllers\API\V1\Commerce\CommerceProductCategoryController;
use App\Http\Controllers\API\V1\Commerce\CommerceProductController;
use App\Http\Controllers\API\V1\Commerce\CommerceReportController;
use App\Http\Controllers\API\V1\Commerce\CommerceSaleController;
use App\Http\Controllers\API\V1\Commerce\CommerceUserController;
use App\Http\Controllers\API\V1\Commerce\PurchaseController;
use App\Http\Controllers\API\V1\Commerce\StockController;
use App\Http\Controllers\API\V1\Commerce\StockExitController;
use App\Http\Controllers\API\V1\Commerce\StockMovementController;
use App\Http\Controllers\API\V1\Commerce\StockTransferController;
use App\Http\Controllers\API\V1\ProductCategoryController;
use App\Http\Controllers\API\V1\MerchantController;
use App\Http\Controllers\API\V1\MarketBlockController;
use App\Http\Controllers\API\V1\MarketController;
use App\Http\Controllers\API\V1\PaymentMethodController;
use App\Http\Controllers\API\V1\PaymentReceiptController;
use App\Http\Controllers\API\V1\PlaceController;
use App\Http\Controllers\API\V1\PlaceRequestController;
use App\Http\Controllers\API\V1\ProductController;
use App\Http\Controllers\API\V1\RentSummaryController;
use App\Http\Controllers\API\V1\SaleController;
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

        Route::get('payment-methods', [PaymentMethodController::class, 'index']);

        Route::post('sales', [SaleController::class, 'store']);
        Route::get('my/sales', [SaleController::class, 'mine']);
        Route::get('sales/{sale}', [SaleController::class, 'show']);

        Route::post('products', [ProductController::class, 'store']);
        Route::put('products/{product}', [ProductController::class, 'update']);
        Route::delete('products/{product}', [ProductController::class, 'destroy']);

        // Admin
        Route::middleware(['permission:manage_markets'])->group(function () {
            Route::post('markets', [MarketController::class, 'store']);
            Route::put('markets/{market}', [MarketController::class, 'update']);
            Route::delete('markets/{market}', [MarketController::class, 'destroy']);
        });

        Route::middleware(['permission:manage_places|view_market_ops'])->group(function () {
            Route::get('place-requests', [PlaceRequestController::class, 'index']);
        });

        Route::middleware(['permission:manage_places'])->group(function () {
            Route::post('markets/{market}/blocks', [MarketBlockController::class, 'store']);
            Route::put('market-blocks/{marketBlock}', [MarketBlockController::class, 'update']);
            Route::delete('market-blocks/{marketBlock}', [MarketBlockController::class, 'destroy']);

            Route::post('places', [PlaceController::class, 'store']);
            Route::put('places/{place}', [PlaceController::class, 'update']);
            Route::delete('places/{place}', [PlaceController::class, 'destroy']);
            Route::post('places/{place}/assign-chief', [PlaceController::class, 'assignChief']);

            Route::post('place-requests/{placeRequest}/approve', [PlaceRequestController::class, 'approve']);
            Route::post('place-requests/{placeRequest}/reject', [PlaceRequestController::class, 'reject']);
        });

        Route::middleware(['permission:manage_receipts|view_market_ops'])->group(function () {
            Route::get('receipts', [PaymentReceiptController::class, 'index']);
            Route::get('rent-summary', RentSummaryController::class);
        });

        Route::middleware(['permission:manage_receipts'])->group(function () {
            Route::post('receipts/{receipt}/approve', [PaymentReceiptController::class, 'approve']);
            Route::post('receipts/{receipt}/reject', [PaymentReceiptController::class, 'reject']);
            Route::post('payment-methods', [PaymentMethodController::class, 'store']);
            Route::put('payment-methods/{paymentMethod}', [PaymentMethodController::class, 'update']);
            Route::delete('payment-methods/{paymentMethod}', [PaymentMethodController::class, 'destroy']);
        });

        Route::middleware(['permission:manage_sales'])->group(function () {
            Route::get('sales', [SaleController::class, 'index']);
        });

        Route::middleware(['permission:manage_users'])->group(function () {
            Route::get('users', [UserController::class, 'index']);
            Route::post('users', [UserController::class, 'store']);
            Route::put('users/{user}', [UserController::class, 'update']);
            Route::delete('users/{user}', [UserController::class, 'destroy']);
        });

        Route::middleware(['permission:manage_categories'])->group(function () {
            Route::get('product-categories/manage', [ProductCategoryController::class, 'manage']);
            Route::post('product-categories', [ProductCategoryController::class, 'store']);
            Route::put('product-categories/{productCategory}', [ProductCategoryController::class, 'update']);
            Route::delete('product-categories/{productCategory}', [ProductCategoryController::class, 'destroy']);
        });

        Route::prefix('commerces/current')->middleware(['commerce'])->group(function () {
            Route::get('/', [CommerceMeController::class, 'show']);
            Route::put('/', [CommerceMeController::class, 'update'])->middleware('commerce:commerce_manage_settings');

            Route::middleware('commerce:commerce_manage_users')->group(function () {
                Route::get('users', [CommerceUserController::class, 'index']);
                Route::post('users', [CommerceUserController::class, 'store']);
                Route::put('users/{commerceUser}', [CommerceUserController::class, 'update']);
            });

            // Catalog / stocks readable by any commerce member (POS needs this).
            Route::get('categories', [CommerceProductCategoryController::class, 'index']);
            Route::get('products', [CommerceProductController::class, 'index']);
            Route::get('products/{product}', [CommerceProductController::class, 'show']);
            Route::get('stocks', [StockController::class, 'index']);
            Route::get('stocks/{stock}', [StockController::class, 'show']);
            Route::get('cash-registers', [CashRegisterController::class, 'index']);
            Route::get('cash-sessions/current', [CashSessionController::class, 'current']);

            Route::middleware('commerce:commerce_manage_products')->group(function () {
                Route::post('categories', [CommerceProductCategoryController::class, 'store']);
                Route::put('categories/{category}', [CommerceProductCategoryController::class, 'update']);
                Route::delete('categories/{category}', [CommerceProductCategoryController::class, 'destroy']);

                Route::post('products', [CommerceProductController::class, 'store']);
                Route::post('products/import', [CommerceProductController::class, 'import']);
                Route::get('products/export', [CommerceProductController::class, 'export']);
                Route::put('products/{product}', [CommerceProductController::class, 'update']);
                Route::delete('products/{product}', [CommerceProductController::class, 'destroy']);
            });

            Route::middleware('commerce:commerce_manage_stocks')->group(function () {
                Route::post('stocks', [StockController::class, 'store']);
                Route::put('stocks/{stock}', [StockController::class, 'update']);

                Route::get('purchases', [PurchaseController::class, 'index']);
                Route::post('purchases', [PurchaseController::class, 'store']);
                Route::get('purchases/{purchase}', [PurchaseController::class, 'show']);

                Route::post('stock-exits', [StockExitController::class, 'store']);

                Route::get('transfers', [StockTransferController::class, 'index']);
                Route::post('transfers', [StockTransferController::class, 'store']);
                Route::get('transfers/{transfer}', [StockTransferController::class, 'show']);

                Route::get('movements', [StockMovementController::class, 'index']);
            });

            Route::middleware('commerce:commerce_manage_cash')->group(function () {
                Route::post('cash-registers', [CashRegisterController::class, 'store']);
                Route::get('cash-registers/{cashRegister}', [CashRegisterController::class, 'show']);
                Route::put('cash-registers/{cashRegister}', [CashRegisterController::class, 'update']);

                Route::post('cash-sessions/open', [CashSessionController::class, 'open']);
                Route::post('cash-sessions/{cashSession}/close', [CashSessionController::class, 'close']);
                Route::post('cash-sessions/movements', [CashSessionController::class, 'storeMovement']);
            });

            Route::middleware('commerce:commerce_manage_sales')->group(function () {
                Route::get('sales', [CommerceSaleController::class, 'index']);
                Route::post('sales', [CommerceSaleController::class, 'store']);
                Route::get('sales/{sale}', [CommerceSaleController::class, 'show']);
            });

            Route::get('dashboard', CommerceDashboardController::class);

            Route::middleware('commerce:commerce_view_reports')->group(function () {
                Route::get('reports/{type}', [CommerceReportController::class, 'show']);
            });
        });

        Route::middleware(['permission:manage_commerces'])->group(function () {
            Route::get('commerces', [CommerceController::class, 'index']);
            Route::post('commerces', [CommerceController::class, 'store']);
            Route::get('commerces/{commerce}', [CommerceController::class, 'show']);
            Route::put('commerces/{commerce}', [CommerceController::class, 'update']);
            Route::post('commerces/{commerce}/owner', [CommerceController::class, 'createOwner']);
        });
    });
});
