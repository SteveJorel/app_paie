<?php

use App\Http\Controllers\Api\V1\Admin\CommissionRuleController;
use App\Http\Controllers\Api\V1\Admin\SupplierController as AdminSupplierController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\SupplierController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'time' => now()->toIso8601String(),
        ]);
    });

    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

        Route::middleware(['auth:sanctum', 'role:client,supplier,admin,finance'])->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::middleware(['auth:sanctum', 'role:client,supplier,admin,finance'])->group(function () {
        Route::get('/suppliers', [SupplierController::class, 'index']);
        Route::get('/suppliers/{supplier}', [SupplierController::class, 'show']);
    });

    // Consultation des règles de commission : administrateur et responsable financier.
    Route::middleware(['auth:sanctum', 'role:admin,finance'])->prefix('admin')->group(function () {
        Route::get('/commission-rules', [CommissionRuleController::class, 'index']);
    });

    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
        Route::get('/ping', fn () => response()->json(['message' => 'admin ok']));

        Route::get('/suppliers', [AdminSupplierController::class, 'index']);
        Route::post('/suppliers', [AdminSupplierController::class, 'store']);
        Route::patch('/suppliers/{supplier}', [AdminSupplierController::class, 'update']);
        Route::post('/suppliers/{supplier}/approve', [AdminSupplierController::class, 'approve']);
        Route::post('/suppliers/{supplier}/suspend', [AdminSupplierController::class, 'suspend']);

        Route::post('/commission-rules', [CommissionRuleController::class, 'store']);
        Route::post('/commission-rules/{commissionRule}/retire', [CommissionRuleController::class, 'retire']);
    });
});
