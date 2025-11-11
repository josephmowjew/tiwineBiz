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
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('api.v1.auth.forgot-password');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('api.v1.auth.reset-password');

        // Email verification (public route with signed URL)
        Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('api.v1.auth.verify-email');

        // Protected routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
            Route::get('/user', [AuthController::class, 'user'])->name('api.v1.auth.user');
            Route::put('/change-password', [AuthController::class, 'changePassword'])->name('api.v1.auth.change-password');
            Route::post('/email/resend', [AuthController::class, 'sendVerificationEmail'])->name('api.v1.auth.resend-verification');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Protected API Routes
    |--------------------------------------------------------------------------
    | All routes below require authentication via Sanctum
    */
    Route::middleware('auth:sanctum')->group(function () {

        // Profile Management
        Route::prefix('profile')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\ProfileController::class, 'show'])->name('api.v1.profile.show');
            Route::put('/', [\App\Http\Controllers\Api\V1\ProfileController::class, 'update'])->name('api.v1.profile.update');
            Route::post('/photo', [\App\Http\Controllers\Api\V1\ProfileController::class, 'uploadPhoto'])->name('api.v1.profile.upload-photo');
            Route::delete('/photo', [\App\Http\Controllers\Api\V1\ProfileController::class, 'deletePhoto'])->name('api.v1.profile.delete-photo');
        });

        // Shop Management
        Route::apiResource('shops', \App\Http\Controllers\Api\V1\ShopController::class);

        // Role Management
        Route::apiResource('roles', \App\Http\Controllers\Api\V1\RoleController::class);

        // Branch Management
        Route::apiResource('branches', \App\Http\Controllers\Api\V1\BranchController::class);
        Route::post('branches/{branch}/users', [\App\Http\Controllers\Api\V1\BranchController::class, 'assignUser']);
        Route::delete('branches/{branch}/users', [\App\Http\Controllers\Api\V1\BranchController::class, 'removeUser']);
        Route::get('branches/{branch}/users', [\App\Http\Controllers\Api\V1\BranchController::class, 'users']);

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

        // Stock Movement Management (Immutable - no updates or deletes)
        Route::apiResource('stock-movements', \App\Http\Controllers\Api\V1\StockMovementController::class)->only(['index', 'store', 'show']);

        // Purchase Order Management
        Route::apiResource('purchase-orders', \App\Http\Controllers\Api\V1\PurchaseOrderController::class);

        // Product Batch Management (Inventory batches with expiry tracking)
        Route::apiResource('product-batches', \App\Http\Controllers\Api\V1\ProductBatchController::class);

        // Exchange Rate Management (Immutable - no updates)
        Route::get('exchange-rates/latest', [\App\Http\Controllers\Api\V1\ExchangeRateController::class, 'latest']);
        Route::apiResource('exchange-rates', \App\Http\Controllers\Api\V1\ExchangeRateController::class)->except(['update']);

        // Mobile Money Transaction Management (Immutable - no updates or deletes)
        Route::apiResource('mobile-money-transactions', \App\Http\Controllers\Api\V1\MobileMoneyTransactionController::class)->only(['index', 'store', 'show']);

        // EFD Transaction Management (Immutable - no updates or deletes)
        Route::apiResource('efd-transactions', \App\Http\Controllers\Api\V1\EfdTransactionController::class)->only(['index', 'store', 'show']);

        // Subscription Management
        Route::apiResource('subscriptions', \App\Http\Controllers\Api\V1\SubscriptionController::class);

        // Subscription Payment Management (Immutable - no updates or deletes)
        Route::apiResource('subscription-payments', \App\Http\Controllers\Api\V1\SubscriptionPaymentController::class)->only(['index', 'store', 'show']);

        /*
        |--------------------------------------------------------------------------
        | Reports & Analytics Routes
        |--------------------------------------------------------------------------
        */

        // Dashboard
        Route::prefix('dashboard')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\DashboardController::class, 'index'])->name('api.v1.dashboard.index');
            Route::get('/sales', [\App\Http\Controllers\Api\V1\DashboardController::class, 'salesOverview'])->name('api.v1.dashboard.sales');
            Route::get('/inventory', [\App\Http\Controllers\Api\V1\DashboardController::class, 'inventoryOverview'])->name('api.v1.dashboard.inventory');
            Route::get('/products', [\App\Http\Controllers\Api\V1\DashboardController::class, 'productInsights'])->name('api.v1.dashboard.products');
            Route::get('/quick-stats', [\App\Http\Controllers\Api\V1\DashboardController::class, 'quickStats'])->name('api.v1.dashboard.quick-stats');
        });

        // Sales Reports
        Route::prefix('reports/sales')->group(function () {
            Route::get('/summary', [\App\Http\Controllers\Api\V1\SalesReportController::class, 'summary'])->name('api.v1.reports.sales.summary');
            Route::get('/daily', [\App\Http\Controllers\Api\V1\SalesReportController::class, 'daily'])->name('api.v1.reports.sales.daily');
            Route::get('/weekly', [\App\Http\Controllers\Api\V1\SalesReportController::class, 'weekly'])->name('api.v1.reports.sales.weekly');
            Route::get('/monthly', [\App\Http\Controllers\Api\V1\SalesReportController::class, 'monthly'])->name('api.v1.reports.sales.monthly');
            Route::get('/comparison', [\App\Http\Controllers\Api\V1\SalesReportController::class, 'comparison'])->name('api.v1.reports.sales.comparison');
            Route::get('/hourly', [\App\Http\Controllers\Api\V1\SalesReportController::class, 'hourly'])->name('api.v1.reports.sales.hourly');
            Route::get('/top-customers', [\App\Http\Controllers\Api\V1\SalesReportController::class, 'topCustomers'])->name('api.v1.reports.sales.top-customers');
            Route::get('/export', [\App\Http\Controllers\Api\V1\SalesReportController::class, 'export'])->name('api.v1.reports.sales.export');
        });

        // Product Reports
        Route::prefix('reports/products')->group(function () {
            Route::get('/top-selling', [\App\Http\Controllers\Api\V1\ProductReportController::class, 'topSelling'])->name('api.v1.reports.products.top-selling');
            Route::get('/slow-moving', [\App\Http\Controllers\Api\V1\ProductReportController::class, 'slowMoving'])->name('api.v1.reports.products.slow-moving');
            Route::get('/{product}/performance', [\App\Http\Controllers\Api\V1\ProductReportController::class, 'performance'])->name('api.v1.reports.products.performance');
            Route::get('/low-stock', [\App\Http\Controllers\Api\V1\ProductReportController::class, 'lowStock'])->name('api.v1.reports.products.low-stock');
            Route::get('/category-performance', [\App\Http\Controllers\Api\V1\ProductReportController::class, 'categoryPerformance'])->name('api.v1.reports.products.category-performance');
        });

        // Inventory Reports
        Route::prefix('reports/inventory')->group(function () {
            Route::get('/valuation', [\App\Http\Controllers\Api\V1\InventoryReportController::class, 'valuation'])->name('api.v1.reports.inventory.valuation');
            Route::get('/movements', [\App\Http\Controllers\Api\V1\InventoryReportController::class, 'movements'])->name('api.v1.reports.inventory.movements');
            Route::get('/aging', [\App\Http\Controllers\Api\V1\InventoryReportController::class, 'aging'])->name('api.v1.reports.inventory.aging');
            Route::get('/alerts', [\App\Http\Controllers\Api\V1\InventoryReportController::class, 'alerts'])->name('api.v1.reports.inventory.alerts');
            Route::get('/turnover', [\App\Http\Controllers\Api\V1\InventoryReportController::class, 'turnover'])->name('api.v1.reports.inventory.turnover');
        });

        /*
        |--------------------------------------------------------------------------
        | Receipt Routes
        |--------------------------------------------------------------------------
        */
        Route::prefix('receipts')->group(function () {
            Route::get('/{sale}/view', [\App\Http\Controllers\Api\V1\ReceiptController::class, 'view'])->name('api.v1.receipts.view');
            Route::get('/{sale}/download', [\App\Http\Controllers\Api\V1\ReceiptController::class, 'download'])->name('api.v1.receipts.download');
            Route::get('/{sale}/html', [\App\Http\Controllers\Api\V1\ReceiptController::class, 'html'])->name('api.v1.receipts.html');
            Route::get('/{sale}/print', [\App\Http\Controllers\Api\V1\ReceiptController::class, 'print'])->name('api.v1.receipts.print');
            Route::post('/{sale}/email', [\App\Http\Controllers\Api\V1\ReceiptController::class, 'email'])->name('api.v1.receipts.email');
        });

        /*
        |--------------------------------------------------------------------------
        | Sync Routes (Offline Support)
        |--------------------------------------------------------------------------
        */
        Route::prefix('sync')->group(function () {
            Route::post('/push', [\App\Http\Controllers\Api\V1\SyncController::class, 'push'])->name('api.v1.sync.push');
            Route::post('/pull', [\App\Http\Controllers\Api\V1\SyncController::class, 'pull'])->name('api.v1.sync.pull');
            Route::get('/status', [\App\Http\Controllers\Api\V1\SyncController::class, 'status'])->name('api.v1.sync.status');
            Route::get('/pending', [\App\Http\Controllers\Api\V1\SyncController::class, 'pending'])->name('api.v1.sync.pending');
            Route::get('/conflicts', [\App\Http\Controllers\Api\V1\SyncController::class, 'conflicts'])->name('api.v1.sync.conflicts');
            Route::post('/conflicts/{queueItem}/resolve', [\App\Http\Controllers\Api\V1\SyncController::class, 'resolveConflict'])->name('api.v1.sync.resolve');
            Route::post('/{queueItem}/retry', [\App\Http\Controllers\Api\V1\SyncController::class, 'retry'])->name('api.v1.sync.retry');
            Route::delete('/{queueItem}', [\App\Http\Controllers\Api\V1\SyncController::class, 'delete'])->name('api.v1.sync.delete');
            Route::get('/history', [\App\Http\Controllers\Api\V1\SyncController::class, 'history'])->name('api.v1.sync.history');
        });

        /*
        |--------------------------------------------------------------------------
        | Notification Routes
        |--------------------------------------------------------------------------
        */
        Route::prefix('notifications')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\NotificationController::class, 'index'])->name('api.v1.notifications.index');
            Route::get('/unread-count', [\App\Http\Controllers\Api\V1\NotificationController::class, 'unreadCount'])->name('api.v1.notifications.unread-count');
            Route::post('/{id}/read', [\App\Http\Controllers\Api\V1\NotificationController::class, 'markAsRead'])->name('api.v1.notifications.mark-as-read');
            Route::post('/read-all', [\App\Http\Controllers\Api\V1\NotificationController::class, 'markAllAsRead'])->name('api.v1.notifications.mark-all-as-read');
            Route::delete('/{id}', [\App\Http\Controllers\Api\V1\NotificationController::class, 'destroy'])->name('api.v1.notifications.destroy');
            Route::get('/preferences', [\App\Http\Controllers\Api\V1\NotificationController::class, 'preferences'])->name('api.v1.notifications.preferences');
            Route::put('/preferences', [\App\Http\Controllers\Api\V1\NotificationController::class, 'updatePreferences'])->name('api.v1.notifications.update-preferences');
        });

    });
});
