<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\VehicleTypeController;
use App\Http\Controllers\Api\VehicleController;

// Public route
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Profil & Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/profile/update', [AuthController::class, 'updateProfile']);

    // User Management (Hanya Admin)
    Route::middleware('role:Admin')->group(function () {
        Route::apiResource('users', UserController::class);
    });

    // Vehicle Management Routes (Akses untuk Admin & Operator)
    Route::middleware('role:Admin|Operator')->group(function () {
        Route::get('/vehicle-types', [VehicleTypeController::class, 'index']);
        Route::apiResource('vehicles', VehicleController::class);

        // Kasih nama route 'vehicles.status' biar kebaca di Form Request
        Route::patch('/vehicles/{vehicle}/status', [VehicleController::class, 'updateStatus'])
            ->name('vehicles.status');
    });
});
