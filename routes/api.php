<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\VehicleTypeController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\GpsProviderApiController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    // Profil & Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/profile/update', [AuthController::class, 'updateProfile']);

    // ==========================================
    // 1. KHUSUS ADMIN
    // ==========================================
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);

        // CRUD GPS Provider (Hexagon, dll)
        Route::apiResource('gps-providers', GpsProviderApiController::class);

        // Hapus kendaraan cuma boleh admin
        Route::delete('vehicles/{vehicle}', [VehicleController::class, 'destroy']);
    });

    // ==========================================
    // 2. ADMIN & OPERATOR
    // ==========================================
    Route::middleware('role:admin|operator')->group(function () {
        // Create, Update, dan ganti status
        Route::post('vehicles', [VehicleController::class, 'store']);
        Route::put('vehicles/{vehicle}', [VehicleController::class, 'update']);
        Route::patch('vehicles/{vehicle}/status', [VehicleController::class, 'updateStatus'])->name('vehicles.status');

        // Link vehicle GPS device ID & provider
        // Sesuai subtask: "Link vehicle GPS device ID"
        Route::patch('vehicles/{vehicle}/gps-link', [VehicleController::class, 'linkGps']);
    });

    // ==========================================
    // 3. AKSES UMUM (ADMIN, OPERATOR, VIEWER)
    // ==========================================
    Route::get('vehicle-types', [VehicleTypeController::class, 'index']);
    Route::get('vehicles', [VehicleController::class, 'index']);
    Route::get('vehicles/{vehicle}', [VehicleController::class, 'show']);
    Route::get('vehicles/{vehicle}/activities', [VehicleController::class, 'activities']);

    // Dropdown list buat milih provider pas di UI assignment
    Route::get('gps-providers-list', [GpsProviderApiController::class, 'list']);
});
