<?php

use Illuminate\Support\Facades\Route;
use Liberu\Billing\Isp\Api\Http\Controllers\IspCapabilityController;

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.isp.read'])->prefix('api/v1/billing/isp/capabilities')->group(function (): void {
    Route::get('/', [IspCapabilityController::class, 'index'])->name('billing.isp.capabilities.index');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.isp.write', 'idempotency'])->prefix('api/v1/billing/isp/capabilities')->group(function (): void {
    Route::post('/', [IspCapabilityController::class, 'store'])->name('billing.isp.capabilities.store');
    Route::patch('/{capability}/lifecycle', [IspCapabilityController::class, 'transition'])->whereNumber('capability')->name('billing.isp.capabilities.lifecycle');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.isp.write', 'idempotency'])->prefix('api/v1/billing/isp')->group(function (): void {
    Route::post('/', [IspCapabilityController::class, 'storeAccessService'])->name('billing.isp.store');
    Route::patch('/{service}/lifecycle', [IspCapabilityController::class, 'transitionAccessService'])->whereNumber('service')->name('billing.isp.services.lifecycle');
    Route::post('/{service}/synchronize', [IspCapabilityController::class, 'synchronizeAccessService'])->whereNumber('service')->name('billing.isp.services.synchronize');
    Route::post('/{service}/accounting', [IspCapabilityController::class, 'recordAccounting'])->whereNumber('service')->name('billing.isp.services.accounting');
    Route::post('/{service}/usage/reset', [IspCapabilityController::class, 'resetUsage'])->whereNumber('service')->name('billing.isp.services.usage.reset');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.isp.read'])->prefix('api/v1/billing/isp')->group(function (): void {
    Route::get('/', [IspCapabilityController::class, 'accounts'])->name('billing.isp.services.index');
    Route::get('/{record}', [IspCapabilityController::class, 'showAccount'])->whereNumber('record')->name('billing.isp.services.show');
});
