<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleManagementController;

// Public routes (temporarily without rate limiting for testing)
Route::post('/register', [AuthController::class, 'register']);

// User login
Route::post('/login', [AuthController::class, 'login']);

// Admin login (separate endpoint)
Route::post('/admin/login', [AuthController::class, 'adminLogin']);

// Protected routes (all authenticated users)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/profile', [AuthController::class, 'profile']);
});

// User routes (role: user, admin, super_admin)
Route::middleware(['auth:sanctum', 'role:user,admin,super_admin'])->group(function () {
    // Cart routes
    Route::get('/cart', [App\Http\Controllers\CartController::class, 'index']);
    Route::post('/cart/add', [App\Http\Controllers\CartController::class, 'add']);
    Route::put('/cart/items/{cartItem}', [App\Http\Controllers\CartController::class, 'update']);
    Route::delete('/cart/items/{cartItem}', [App\Http\Controllers\CartController::class, 'remove']);
    Route::delete('/cart/clear', [App\Http\Controllers\CartController::class, 'clear']);
    
    // Order routes
    Route::get('/orders', [App\Http\Controllers\OrderController::class, 'index']);
    Route::get('/orders/{order}', [App\Http\Controllers\OrderController::class, 'show']);
    Route::post('/orders', [App\Http\Controllers\OrderController::class, 'store']);
    
    // Address routes
    Route::get('/addresses', [App\Http\Controllers\AddressController::class, 'index']);
    Route::post('/addresses', [App\Http\Controllers\AddressController::class, 'store']);
    Route::put('/addresses/{address}', [App\Http\Controllers\AddressController::class, 'update']);
    Route::delete('/addresses/{address}', [App\Http\Controllers\AddressController::class, 'destroy']);
    Route::put('/addresses/{address}/default', [App\Http\Controllers\AddressController::class, 'setDefault']);
    
    // Payment routes
    Route::post('/orders/{order}/payment/intent', [App\Http\Controllers\PaymentController::class, 'createPaymentIntent']);
    Route::get('/orders/{order}/payment/status', [App\Http\Controllers\PaymentController::class, 'getPaymentStatus']);
    Route::post('/orders/{order}/payment/confirm', [App\Http\Controllers\PaymentController::class, 'confirmPayment']);
    Route::get('/orders/{order}/payment/transactions', [App\Http\Controllers\PaymentController::class, 'getTransactions']);
});

// Admin routes (role: admin, super_admin)
Route::middleware(['auth:sanctum', 'role:admin,super_admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', [App\Http\Controllers\AdminController::class, 'dashboard']);
    
    // User Management
    Route::get('/users', [App\Http\Controllers\Admin\UserManagementController::class, 'index']);
    Route::get('/users/{user}', [App\Http\Controllers\Admin\UserManagementController::class, 'show']);
    Route::put('/users/{user}', [App\Http\Controllers\Admin\UserManagementController::class, 'update']);
    Route::delete('/users/{user}', [App\Http\Controllers\Admin\UserManagementController::class, 'destroy']);
    Route::post('/users/{user}/toggle-lock', [App\Http\Controllers\Admin\UserManagementController::class, 'toggleLock']);
    Route::get('/users-statistics', [App\Http\Controllers\Admin\UserManagementController::class, 'statistics']);
    Route::get('/role-change-logs', [App\Http\Controllers\Admin\UserManagementController::class, 'roleChangeLogs']);
    
    // Product Management
    Route::get('/products', [App\Http\Controllers\AdminController::class, 'products']);
    Route::post('/products', [App\Http\Controllers\AdminController::class, 'createProduct']);
    Route::put('/products/{product}', [App\Http\Controllers\AdminController::class, 'updateProduct']);
    Route::delete('/products/{product}', [App\Http\Controllers\AdminController::class, 'deleteProduct']);
    Route::put('/products/{product}/stock', [App\Http\Controllers\AdminController::class, 'updateStock']);
    
    // Product Images
    Route::delete('/product-images/{image}', [App\Http\Controllers\AdminController::class, 'deleteProductImage']);
    Route::put('/product-images/{image}/primary', [App\Http\Controllers\AdminController::class, 'setPrimaryImage']);
    
    // Category Management
    Route::get('/categories', [App\Http\Controllers\AdminController::class, 'getCategories']);
    
    // Order Management
    Route::get('/orders', [App\Http\Controllers\Admin\OrderController::class, 'index']);
    Route::get('/orders/{order}', [App\Http\Controllers\Admin\OrderController::class, 'show']);
    Route::put('/orders/{order}/status', [App\Http\Controllers\Admin\OrderController::class, 'updateStatus']);
    Route::put('/orders/{order}/payment-status', [App\Http\Controllers\Admin\OrderController::class, 'updatePaymentStatus']);
    Route::get('/orders/statistics', [App\Http\Controllers\Admin\OrderController::class, 'statistics']);
    Route::get('/orders/export', [App\Http\Controllers\Admin\OrderController::class, 'export']);
    
    // Color Management
    Route::get('/colors', [App\Http\Controllers\Admin\ColorController::class, 'index']);
    Route::post('/colors', [App\Http\Controllers\Admin\ColorController::class, 'store']);
    Route::get('/colors/{color}', [App\Http\Controllers\Admin\ColorController::class, 'show']);
    Route::put('/colors/{color}', [App\Http\Controllers\Admin\ColorController::class, 'update']);
    Route::delete('/colors/{color}', [App\Http\Controllers\Admin\ColorController::class, 'destroy']);
    Route::post('/colors/{color}/toggle-status', [App\Http\Controllers\Admin\ColorController::class, 'toggleStatus']);
    Route::post('/colors/sort-order', [App\Http\Controllers\Admin\ColorController::class, 'updateSortOrder']);
    
    // Size Management
    Route::get('/sizes', [App\Http\Controllers\Admin\SizeController::class, 'index']);
    Route::post('/sizes', [App\Http\Controllers\Admin\SizeController::class, 'store']);
    Route::get('/sizes/{size}', [App\Http\Controllers\Admin\SizeController::class, 'show']);
    Route::put('/sizes/{size}', [App\Http\Controllers\Admin\SizeController::class, 'update']);
    Route::delete('/sizes/{size}', [App\Http\Controllers\Admin\SizeController::class, 'destroy']);
    Route::post('/sizes/{size}/toggle-status', [App\Http\Controllers\Admin\SizeController::class, 'toggleStatus']);
    Route::post('/sizes/sort-order', [App\Http\Controllers\Admin\SizeController::class, 'updateSortOrder']);
    
    // Payment Management
    Route::post('/orders/{order}/refund', [App\Http\Controllers\PaymentController::class, 'refundOrder']);
    Route::post('/orders/{order}/refund/partial', [App\Http\Controllers\PaymentController::class, 'partialRefund']);
    Route::get('/payment/analytics', [App\Http\Controllers\PaymentController::class, 'analytics']);
});

// Super Admin routes (role: super_admin only)
Route::middleware(['auth:sanctum', 'role:super_admin'])->prefix('admin')->group(function () {
    // Role management
    Route::post('/users/{user}/promote', [RoleManagementController::class, 'promote']);
    Route::post('/users/{user}/demote', [RoleManagementController::class, 'demote']);
    
    // Audit logs
    Route::get('/role-change-logs', [RoleManagementController::class, 'getRoleChangeLogs']);
    Route::get('/users/{user}/role-change-logs', [RoleManagementController::class, 'getUserRoleChangeLogs']);
});

// Public product routes (no auth required)
Route::get('/products', [App\Http\Controllers\ProductController::class, 'index']);
Route::get('/products/{slug}', [App\Http\Controllers\ProductController::class, 'show']);
Route::get('/categories', [App\Http\Controllers\CategoryController::class, 'index']);
Route::get('/categories/{category}', [App\Http\Controllers\CategoryController::class, 'show']);

// Stripe webhook (no auth required)
Route::post('/webhook/stripe', [App\Http\Controllers\PaymentController::class, 'webhook']);

// Health check
Route::get('/test', function () {
    return response()->json([
        'status' => 'API Working',
        'timestamp' => now()->toISOString(),
        'version' => '2.0.0',
        'rbac' => 'enabled'
    ]);
});
