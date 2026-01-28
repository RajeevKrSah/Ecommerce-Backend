<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;

// Public routes with rate limiting
Route::middleware(['throttle:register'])->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware(['throttle:login'])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/profile', [AuthController::class, 'profile']);
    
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
    
    // Admin order routes
    Route::get('/admin/orders', [App\Http\Controllers\OrderController::class, 'adminIndex']);
    Route::put('/admin/orders/{order}/status', [App\Http\Controllers\OrderController::class, 'updateStatus']);
});

// Admin routes (requires admin role)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\AdminController::class, 'dashboard']);
    Route::get('/users', [App\Http\Controllers\AdminController::class, 'users']);
    Route::put('/users/{user}/role', [App\Http\Controllers\AdminController::class, 'updateUserRole']);
    Route::get('/products', [App\Http\Controllers\AdminController::class, 'products']);
    Route::post('/products', [App\Http\Controllers\AdminController::class, 'createProduct']);
    Route::put('/products/{product}', [App\Http\Controllers\AdminController::class, 'updateProduct']);
    Route::delete('/products/{product}', [App\Http\Controllers\AdminController::class, 'deleteProduct']);
    Route::put('/products/{product}/stock', [App\Http\Controllers\AdminController::class, 'updateStock']);
    Route::get('/categories', [App\Http\Controllers\AdminController::class, 'getCategories']);
    Route::delete('/product-images/{image}', [App\Http\Controllers\AdminController::class, 'deleteProductImage']);
    Route::put('/product-images/{image}/primary', [App\Http\Controllers\AdminController::class, 'setPrimaryImage']);
    
    // Admin payment routes
    Route::post('/orders/{order}/refund', [App\Http\Controllers\PaymentController::class, 'refundOrder']);
    Route::post('/orders/{order}/refund/partial', [App\Http\Controllers\PaymentController::class, 'partialRefund']);
    Route::get('/payment/analytics', [App\Http\Controllers\PaymentController::class, 'analytics']);
});

// Public product routes (no auth required)
Route::get('/products', [App\Http\Controllers\ProductController::class, 'index']);
Route::get('/products/{slug}', [App\Http\Controllers\ProductController::class, 'show']);
Route::get('/categories', [App\Http\Controllers\CategoryController::class, 'index']);
Route::get('/categories/{category}', [App\Http\Controllers\CategoryController::class, 'show']);

// Protected product management routes (admin only)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/products', [App\Http\Controllers\ProductController::class, 'store']);
    Route::put('/products/{product}', [App\Http\Controllers\ProductController::class, 'update']);
    Route::delete('/products/{product}', [App\Http\Controllers\ProductController::class, 'destroy']);
    
    Route::post('/categories', [App\Http\Controllers\CategoryController::class, 'store']);
    Route::put('/categories/{category}', [App\Http\Controllers\CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [App\Http\Controllers\CategoryController::class, 'destroy']);
});

// Stripe webhook (no auth required)
Route::post('/webhook/stripe', [App\Http\Controllers\PaymentController::class, 'webhook']);

// Health check
Route::get('/test', function () {
    return response()->json([
        'status' => 'API Working',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0'
    ]);
});
