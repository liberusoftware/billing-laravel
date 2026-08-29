<?php

use Illuminate\Support\Facades\Route;
use Liberu\Billing\CustomerPortal\Api\Http\Controllers\CustomerBillingController;
use Liberu\Billing\CustomerPortal\Api\Http\Controllers\PortalItemController;
use Liberu\Billing\CustomerPortal\Api\Http\Controllers\PortalRequestController;

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.customer-portal.read'])->prefix('api/v1/billing/customer-portal/items')->group(function (): void {
    Route::get('/', [PortalItemController::class, 'index'])->name('billing.customer-portal.items.index');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.customer-portal.write', 'idempotency'])->prefix('api/v1/billing/customer-portal/items')->group(function (): void {
    Route::post('/', [PortalItemController::class, 'store'])->name('billing.customer-portal.items.store');
    Route::patch('/{item}/status', [PortalItemController::class, 'transition'])->whereNumber('item')->name('billing.customer-portal.items.status');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.customer-portal.read'])->prefix('api/v1/billing/customer-portal')->group(function (): void {
    Route::get('/billing/{customer}', [CustomerBillingController::class, 'show'])->whereNumber('customer')->name('billing.customer-portal.billing.show');
    Route::get('/', [PortalRequestController::class, 'index'])->name('billing.customer-portal.requests.index');
    Route::get('/{record}', [PortalRequestController::class, 'show'])->whereNumber('record')->name('billing.customer-portal.requests.show');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.customer-portal.write', 'idempotency'])->prefix('api/v1/billing/customer-portal')->group(function (): void {
    Route::post('/', [PortalRequestController::class, 'store'])->name('billing.customer-portal.requests.store');
    Route::patch('/{record}/status', [PortalRequestController::class, 'transition'])->whereNumber('record')->name('billing.customer-portal.requests.status');
});
