<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\Billing\Core\Api\Http\Controllers\BillingAccountController;
use Liberu\Billing\Core\Api\Http\Controllers\BillingCoreRecordController;

Route::middleware(['api', 'auth:sanctum', 'ability:billing.billing-core.read'])
    ->prefix('api/v1/billing/billing-core')
    ->name('billing.core.')
    ->group(function (): void {
        Route::get('/', [BillingAccountController::class, 'index'])->name('accounts.index');
        Route::get('/{type}', [BillingCoreRecordController::class, 'index'])->whereIn('type', ['contacts', 'currencies', 'tax-profiles', 'sequences', 'terms', 'settings'])->name('records.index');
    });

Route::middleware(['api', 'auth:sanctum', 'ability:billing.billing-core.write'])
    ->prefix('api/v1/billing/billing-core')
    ->name('billing.core.')
    ->group(function (): void {
        Route::post('/', [BillingAccountController::class, 'store'])->name('accounts.store');
        Route::post('/{type}', [BillingCoreRecordController::class, 'store'])->whereIn('type', ['contacts', 'currencies', 'tax-profiles', 'sequences', 'terms', 'settings'])->name('records.store');
        Route::patch('/{type}/{record}', [BillingCoreRecordController::class, 'update'])->whereIn('type', ['contacts', 'currencies', 'tax-profiles', 'sequences', 'terms', 'settings'])->name('records.update');
        Route::delete('/{type}/{record}', [BillingCoreRecordController::class, 'destroy'])->whereIn('type', ['contacts', 'currencies', 'tax-profiles', 'sequences', 'terms', 'settings'])->name('records.destroy');
    });
