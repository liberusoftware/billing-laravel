<?php

use Illuminate\Support\Facades\Route;
use Liberu\Billing\Hosting\Api\Http\Controllers\HostingCapabilityController;

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.hosting.read'])->prefix('api/v1/billing/hosting/capabilities')->group(function (): void {
    Route::get('/', [HostingCapabilityController::class, 'index'])->name('billing.hosting.capabilities.index');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.hosting.write', 'idempotency'])->prefix('api/v1/billing/hosting/capabilities')->group(function (): void {
    Route::post('/', [HostingCapabilityController::class, 'store'])->name('billing.hosting.capabilities.store');
    Route::patch('/{capability}/lifecycle', [HostingCapabilityController::class, 'transition'])->name('billing.hosting.capabilities.lifecycle');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.hosting.write', 'idempotency'])->prefix('api/v1/billing/hosting')->group(function (): void {
    Route::post('/', [HostingCapabilityController::class, 'storeAccount'])->name('billing.hosting.store');
    Route::post('/{account}/operation', [HostingCapabilityController::class, 'operation'])->whereNumber('account')->name('billing.hosting.accounts.operation');
    Route::patch('/{account}/lifecycle', [HostingCapabilityController::class, 'transitionAccount'])->whereNumber('account')->name('billing.hosting.accounts.lifecycle');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.hosting.read'])->prefix('api/v1/billing/hosting')->group(function (): void {
    Route::get('/', [HostingCapabilityController::class, 'accounts'])->name('billing.hosting.accounts.index');
    Route::get('/{record}', [HostingCapabilityController::class, 'showAccount'])->whereNumber('record')->name('billing.hosting.accounts.show');
});
