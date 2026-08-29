<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\Billing\Core\Api\Http\Controllers\BillingAccountController;
use Liberu\Billing\Core\Api\Http\Controllers\BillingCoreRecordController;

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.billing-core.read'])
    ->prefix('api/v1/billing/billing-core')
    ->name('billing.core.')
    ->group(function (): void {
        Route::get('/', [BillingAccountController::class, 'index'])->name('accounts.index');
        Route::post('/currencies/convert', [BillingCoreRecordController::class, 'convertCurrency'])->name('currencies.convert');
        Route::post('/tax/calculate', [BillingCoreRecordController::class, 'calculateTax'])->name('tax.calculate');
        Route::get('/{type}', [BillingCoreRecordController::class, 'index'])->whereIn('type', ['contacts', 'currencies', 'tax-profiles', 'tax-exemptions', 'sequences', 'terms', 'settings'])->name('records.index');
    });

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.billing-core.write', 'idempotency'])
    ->prefix('api/v1/billing/billing-core')
    ->name('billing.core.')
    ->group(function (): void {
        Route::post('/', [BillingAccountController::class, 'store'])->name('accounts.store');
        Route::patch('/{account}', [BillingAccountController::class, 'update'])->whereNumber('account')->name('accounts.update');
        Route::delete('/{account}', [BillingAccountController::class, 'destroy'])->whereNumber('account')->name('accounts.destroy');
        Route::patch('/{account}/status', [BillingAccountController::class, 'transition'])->whereNumber('account')->name('accounts.status');
        Route::post('/{type}', [BillingCoreRecordController::class, 'store'])->whereIn('type', ['contacts', 'currencies', 'tax-profiles', 'tax-exemptions', 'sequences', 'terms', 'settings'])->name('records.store');
        Route::patch('/{type}/{record}', [BillingCoreRecordController::class, 'update'])->whereIn('type', ['contacts', 'currencies', 'tax-profiles', 'tax-exemptions', 'sequences', 'terms', 'settings'])->name('records.update');
        Route::delete('/{type}/{record}', [BillingCoreRecordController::class, 'destroy'])->whereIn('type', ['contacts', 'currencies', 'tax-profiles', 'tax-exemptions', 'sequences', 'terms', 'settings'])->name('records.destroy');
    });
