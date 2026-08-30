<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\Billing\Payments\Api\Http\Controllers\PaymentController;
use Liberu\Billing\Payments\Api\Http\Controllers\PaymentMethodController;

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.payments.read'])
    ->prefix('api/v1/billing/payments')
    ->name('billing.payments.')
    ->group(function (): void {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::get('/methods', [PaymentMethodController::class, 'methods'])->name('methods.index');
        Route::get('/mandates', [PaymentMethodController::class, 'mandates'])->name('mandates.index');
    });

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.payments.write', 'idempotency'])
    ->prefix('api/v1/billing/payments')
    ->name('billing.payments.')
    ->group(function (): void {
        Route::post('/', [PaymentController::class, 'store'])->name('store');
        Route::post('/methods', [PaymentMethodController::class, 'storeMethod'])->name('methods.store');
        Route::post('/mandates', [PaymentMethodController::class, 'storeMandate'])->name('mandates.store');
        Route::patch('/methods/{method}/status', [PaymentMethodController::class, 'transitionMethod'])->whereNumber('method')->name('methods.status');
        Route::patch('/mandates/{mandate}/status', [PaymentMethodController::class, 'transitionMandate'])->whereNumber('mandate')->name('mandates.status');
        Route::post('/{payment}/capture', [PaymentController::class, 'capture'])->name('capture');
        Route::post('/{payment}/refund', [PaymentController::class, 'refund'])->name('refund');
        Route::post('/{payment}/dispute', [PaymentController::class, 'dispute'])->name('dispute');
        Route::post('/{payment}/allocate', [PaymentController::class, 'allocate'])->name('allocate');
        Route::post('/{payment}/reconcile', [PaymentController::class, 'reconcile'])->name('reconcile');
    });
