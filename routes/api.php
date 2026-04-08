<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\VehicleTypeController;
use App\Http\Controllers\Api\VehicleController;

// Import controller lain (di-comment sementara jika file belum dibuat)
// use App\Http\Controllers\GpsProviderController;
// use App\Http\Controllers\MapController;
// use App\Http\Controllers\OverlayController;
// use App\Http\Controllers\DashboardController;

// Authentication routes
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    // Profile and session management
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/profile/update', [AuthController::class, 'updateProfile']);

    // Admin exclusive routes
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::delete('vehicles/{vehicle}', [VehicleController::class, 'destroy']);

        // Route::apiResource('gps-providers', GpsProviderController::class)->except(['index', 'show']);
    });

    // Admin and Operator routes
    Route::middleware('role:admin|operator')->group(function () {
        Route::apiResource('vehicles', VehicleController::class)->except(['index', 'destroy']);
        Route::patch('/vehicles/{vehicle}/status', [VehicleController::class, 'updateStatus'])
            ->name('vehicles.status');

        // Route::post('overlays', [OverlayController::class, 'store']);
    });

    // General authenticated access (Admin, Operator, Viewer)
    Route::get('/vehicle-types', [VehicleTypeController::class, 'index']);
    Route::get('/vehicles', [VehicleController::class, 'index']);

    // Route::get('map/live', [MapController::class, 'live']);
    // Route::get('map/history', [MapController::class, 'history']);
    // Route::get('overlays', [OverlayController::class, 'index']);
    // Route::get('dashboard', [DashboardController::class, 'index']);
});
