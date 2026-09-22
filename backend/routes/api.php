<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommandController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\MdmStatusController;
use App\Http\Controllers\Api\NanoMdmWebhookController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/webhooks/nanomdm', NanoMdmWebhookController::class);

    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/dashboard', DashboardController::class);
        Route::get('/mdm/status', MdmStatusController::class);

        Route::apiResource('organizations', OrganizationController::class)->except(['destroy']);
        Route::apiResource('users', UserController::class)->only(['index', 'store']);

        Route::get('/devices', [DeviceController::class, 'index']);
        Route::post('/devices', [DeviceController::class, 'store']);
        Route::get('/devices/{device}', [DeviceController::class, 'show']);
        Route::patch('/devices/{device}', [DeviceController::class, 'update']);
        Route::get('/devices/{device}/commands', [DeviceController::class, 'commands']);
        Route::post('/devices/{device}/refresh', [DeviceController::class, 'refresh']);
        Route::post('/devices/{device}/lock', [DeviceController::class, 'lock']);
        Route::post('/devices/{device}/lost-mode', [DeviceController::class, 'enableLostMode']);
        Route::post('/devices/{device}/release', [DeviceController::class, 'disableLostMode']);
        Route::post('/devices/{device}/erase', [DeviceController::class, 'erase']);

        Route::get('/enrollments', [EnrollmentController::class, 'index']);
        Route::post('/enrollments', [EnrollmentController::class, 'store']);
        Route::get('/enrollments/{enrollment}', [EnrollmentController::class, 'show']);

        Route::get('/commands', [CommandController::class, 'index']);
        Route::get('/commands/{command}', [CommandController::class, 'show']);

        Route::get('/audit-logs', [AuditLogController::class, 'index']);
    });
});
