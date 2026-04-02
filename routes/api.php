<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

// Public route
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/profile/update', [AuthController::class, 'updateProfile']);


    // Cuma Admin yang bisa akses CRUD User
    Route::middleware('role:Admin')->group(function () {
        Route::apiResource('users', UserController::class);
    });

    // Nanti kalau ada fitur Operator/Viewer, taruh di bawah sini
});
