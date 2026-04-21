<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\VehicleTypeController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\GpsProviderApiController;
use App\Http\Controllers\Api\MapController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/profile/update', [AuthController::class, 'updateProfile']);

    // Admin Only
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::apiResource('gps-providers', GpsProviderApiController::class);
        Route::get('vehicles/{vehicle}/tracking', [VehicleController::class, 'tracking']);
        Route::delete('vehicles/{vehicle}', [VehicleController::class, 'destroy']);
    });

    // Admin & Operator
    Route::middleware('role:admin|operator')->group(function () {
        Route::post('vehicles', [VehicleController::class, 'store']);
        Route::put('vehicles/{vehicle}', [VehicleController::class, 'update']);
        Route::patch('vehicles/{vehicle}/status', [VehicleController::class, 'updateStatus'])->name('vehicles.status');
        Route::patch('vehicles/{vehicle}/gps-link', [VehicleController::class, 'linkGps']);

        Route::get('gps-providers', [GpsProviderApiController::class, 'index']);
        Route::get('gps-providers/{id}', [GpsProviderApiController::class, 'show']);
    });

    // Public Access (Admin, Operator, Viewer)
    Route::get('vehicle-types', [VehicleTypeController::class, 'index']);
    Route::get('vehicles', [VehicleController::class, 'index']);
    Route::get('vehicles/{vehicle}', [VehicleController::class, 'show']);
    Route::get('vehicles/{vehicle}/activities', [VehicleController::class, 'activities']);
    Route::get('gps-providers-list', [GpsProviderApiController::class, 'list']);
    Route::get('map/live', [MapController::class, 'live']);
});
