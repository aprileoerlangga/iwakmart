<?php

use App\Http\Controllers\API\AddressController;
use App\Http\Controllers\API\AppointmentController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\MessageController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\ReviewController;
use App\Http\Controllers\API\SellerController;
use App\Http\Controllers\API\SellerLocationController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Products
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/featured', [ProductController::class, 'featured']);
Route::get('/products/search/{keyword}', [ProductController::class, 'search']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// Categories
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
Route::get('/categories/{category}/products', [ProductController::class, 'byCategory']);

// Reviews (public read)
Route::get('/products/{product}/reviews', [ReviewController::class, 'productReviews']);

// Seller locations (public)
Route::get('/seller-locations', [SellerLocationController::class, 'index']);
Route::get('/seller-locations/{sellerLocation}', [SellerLocationController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user', [AuthController::class, 'update']);
    
    // Addresses
    Route::apiResource('addresses', AddressController::class);
    Route::put('/addresses/{address}/set-as-main', [AddressController::class, 'setAsMain']);
    
    // Cart
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'addToCart']);
    Route::put('/cart/{cartItem}', [CartController::class, 'updateCartItem']);
    Route::delete('/cart/{cartItem}', [CartController::class, 'removeFromCart']);
    Route::delete('/cart', [CartController::class, 'clearCart']);
    
    // Orders - PERBAIKAN ROUTES
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders/checkout', [OrderController::class, 'checkout']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::get('/orders/{order}/items', [OrderController::class, 'items']);

    // TAMBAHAN ROUTES YANG HILANG:
    Route::put('/orders/{order}/cancel', [OrderController::class, 'cancelOrder']);
    Route::put('/orders/{order}/complete', [OrderController::class, 'completeOrder']);
    Route::put('/orders/{order}/status', [OrderController::class, 'updateOrderStatus']);
    
    // Reviews
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);
    
    // Appointments
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::get('/appointments/{appointment}', [AppointmentController::class, 'show']);
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update']);
    Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy']);
    Route::put('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);
    
    // Messages
    Route::get('/messages/{userId}', [MessageController::class, 'getConversation']);
    Route::post('/messages', [MessageController::class, 'sendMessage']);
    Route::put('/messages/{message}/read', [MessageController::class, 'markAsRead']);
    Route::get('/conversations', [MessageController::class, 'getConversations']);
    
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{notification}', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    
    // Seller routes
    Route::middleware('role:seller')->group(function () {
        // Dashboard
        Route::get('/seller/dashboard', [SellerController::class, 'dashboard']);
        
        // Products
        Route::get('/seller/products', [ProductController::class, 'sellerProducts']);
        Route::post('/seller/products', [ProductController::class, 'store']);
        Route::put('/seller/products/{product}', [ProductController::class, 'update']);
        Route::delete('/seller/products/{product}', [ProductController::class, 'destroy']);
        
        // Orders - PERBAIKAN UNTUK SELLER
        Route::get('/seller/orders', [OrderController::class, 'index']); // Gunakan method yang sama
        Route::put('/seller/orders/{order}/status', [OrderController::class, 'updateOrderStatus']);
        
        // Reviews
        Route::post('/reviews/{review}/reply', [ReviewController::class, 'reply']);
        
        // Appointments
        Route::get('/seller/appointments', [AppointmentController::class, 'sellerAppointments']);
        
        // Locations
        Route::get('/seller/locations', [SellerLocationController::class, 'sellerLocations']);
        Route::post('/seller/locations', [SellerLocationController::class, 'store']);
        Route::put('/seller/locations/{sellerLocation}', [SellerLocationController::class, 'update']);
        Route::delete('/seller/locations/{sellerLocation}', [SellerLocationController::class, 'destroy']);
    });
});