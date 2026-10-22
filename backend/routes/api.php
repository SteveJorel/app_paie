<?php

use App\Http\Controllers\Api\V1\Admin\CommissionRuleController;
use App\Http\Controllers\Api\V1\Admin\SupplierController as AdminSupplierController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\WebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'time' => now()->toIso8601String(),
        ]);
    });

    // Événements du prestataire : pas de jeton, l'authenticité repose sur la signature.
    Route::post('/webhooks/{provider}', [WebhookController::class, 'handle'])
        ->where('provider', '[a-z0-9_]+')
        ->middleware('throttle:120,1');

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

    // Commandes et paiements : réservés aux clients, chacun ne voit que les siens.
    Route::middleware(['auth:sanctum', 'role:client'])->group(function () {
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::post('/orders/{order}/payment-intent', [PaymentController::class, 'intent'])->middleware('throttle:20,1');
        Route::get('/payments/{payment}', [PaymentController::class, 'show']);
    });

    // Historique et reçus du client.
    Route::middleware(['auth:sanctum', 'role:client'])->prefix('me')->group(function () {
        Route::get('/transactions', [TransactionController::class, 'index']);
        Route::get('/transactions/{payment}', [TransactionController::class, 'show']);
        Route::get('/transactions/{payment}/receipt', [TransactionController::class, 'receipt']);
        Route::get('/receipts/{receipt}/download', [TransactionController::class, 'download']);
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