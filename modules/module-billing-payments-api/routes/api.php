<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\Billing\Payments\Api\Http\Controllers\PaymentController;

Route::middleware(['api', 'auth:sanctum', 'ability:billing.payments.read'])
    ->prefix('api/v1/billing/payments')
    ->name('billing.payments.')
    ->group(function (): void {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
    });

Route::middleware(['api', 'auth:sanctum', 'ability:billing.payments.write'])
    ->prefix('api/v1/billing/payments')
    ->name('billing.payments.')
    ->group(function (): void {
        Route::post('/', [PaymentController::class, 'store'])->name('store');
        Route::post('/{payment}/capture', [PaymentController::class, 'capture'])->name('capture');
        Route::post('/{payment}/refund', [PaymentController::class, 'refund'])->name('refund');
        Route::post('/{payment}/dispute', [PaymentController::class, 'dispute'])->name('dispute');
    });
