<?php

use App\Http\Controllers\Api\DeviceAuthController;
use App\Http\Controllers\Api\VaultApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Device authorization flow (unauthenticated)
    Route::post('auth/device/code', [DeviceAuthController::class, 'requestDeviceCode']);
    Route::post('auth/device/token', [DeviceAuthController::class, 'pollDeviceToken']);

    // Authenticated API routes (Sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('whoami', [VaultApiController::class, 'whoami']);
        Route::get('projects', [VaultApiController::class, 'listProjects']);
        Route::post('projects', [VaultApiController::class, 'createProject']);

        Route::get('projects/{slug}/variables', [VaultApiController::class, 'listVariables']);
        Route::get('projects/{slug}/variables/{key}', [VaultApiController::class, 'inspectVariable']);
        Route::get('projects/{slug}/variables/{key}/value', [VaultApiController::class, 'getVariableValue']);
        Route::post('variables/set', [VaultApiController::class, 'setVariable']);

        Route::get('projects/{slug}/template', [VaultApiController::class, 'template']);
        Route::get('projects/{slug}/diff', [VaultApiController::class, 'diff']);
    });
});
