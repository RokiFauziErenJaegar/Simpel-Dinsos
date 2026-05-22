<?php

use App\Http\Controllers\Api\V1\AuthApiController;
use App\Http\Controllers\Api\V1\ServicesApiController;
use Illuminate\Support\Facades\Route;

/*
 * SIMPEL DINSOS — REST API v1 untuk Mobile App
 *
 * Base URL: /api/v1
 * Auth: Bearer token via Laravel Sanctum
 *
 * Flow:
 *   1. POST /api/v1/auth/send-otp     → kirim OTP ke nomor HP
 *   2. POST /api/v1/auth/verify-otp   → verifikasi & dapatkan token
 *   3. Gunakan token di header: Authorization: Bearer {token}
 */
Route::prefix('v1')->group(function () {

    // Public endpoints
    Route::get('/services', [ServicesApiController::class, 'index']);
    Route::get('/services/{slug}', [ServicesApiController::class, 'show']);
    Route::get('/queue/status', [ServicesApiController::class, 'queueStatus']);
    Route::get('/applications/{code}', [ServicesApiController::class, 'applicationStatus']);

    // Auth
    Route::post('/auth/send-otp', [AuthApiController::class, 'sendOtp']);
    Route::post('/auth/verify-otp', [AuthApiController::class, 'verifyOtp']);

    // Authenticated
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthApiController::class, 'me']);
        Route::post('/auth/logout', [AuthApiController::class, 'logout']);
        Route::get('/my/applications', [ServicesApiController::class, 'myApplications']);
    });

});
