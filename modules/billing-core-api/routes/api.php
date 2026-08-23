<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\Billing\Core\Api\Http\Controllers\BillingAccountController;

Route::middleware(['api', 'auth:sanctum', 'ability:billing.billing-core.read'])
    ->prefix('api/v1/billing/billing-core')
    ->name('billing.core.')
    ->group(function (): void {
        Route::get('/', [BillingAccountController::class, 'index'])->name('accounts.index');
    });

Route::middleware(['api', 'auth:sanctum', 'ability:billing.billing-core.write'])
    ->prefix('api/v1/billing/billing-core')
    ->name('billing.core.')
    ->group(function (): void {
        Route::post('/', [BillingAccountController::class, 'store'])->name('accounts.store');
    });
