<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\Billing\Subscriptions\Api\Http\Controllers\SubscriptionController;

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.subscriptions.read'])
    ->prefix('api/v1/billing/subscriptions')
    ->name('billing.subscriptions.')
    ->group(function (): void {
        Route::get('/', [SubscriptionController::class, 'index'])->name('index');
    });

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.subscriptions.write', 'idempotency'])
    ->prefix('api/v1/billing/subscriptions')
    ->name('billing.subscriptions.')
    ->group(function (): void {
        Route::post('/', [SubscriptionController::class, 'store'])->name('store');
        Route::post('/{subscription}/renew', [SubscriptionController::class, 'renew'])->name('renew');
        Route::post('/{subscription}/pause', [SubscriptionController::class, 'pause'])->name('pause');
        Route::post('/{subscription}/resume', [SubscriptionController::class, 'resume'])->name('resume');
        Route::post('/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
        Route::patch('/{subscription}/plan', [SubscriptionController::class, 'changePlan'])->name('plan');
        Route::patch('/{subscription}/entitlements', [SubscriptionController::class, 'entitlements'])->name('entitlements');
    });
