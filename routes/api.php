<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\VehicleTypeController;
use App\Http\Controllers\Api\VehicleController;

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
    });

    // ==========================================
    // 3. AKSES UMUM (ADMIN, OPERATOR, VIEWER)
    // ==========================================
    Route::get('vehicle-types', [VehicleTypeController::class, 'index']);

    // Semua role bisa liat list kendaraan
    Route::get('vehicles', [VehicleController::class, 'index']);

    // FIX VIEWER: Semua role bisa liat detail kendaraan
    Route::get('vehicles/{vehicle}', [VehicleController::class, 'show']);

    // Endpoint untuk ngambil log aktivitas kendaraan
    // (Biar API-nya gak error 404 pas masuk halaman detail)
    Route::get('vehicles/{vehicle}/activities', [VehicleController::class, 'activities']);
});
