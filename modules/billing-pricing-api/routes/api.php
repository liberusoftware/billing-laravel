<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Liberu\Billing\Pricing\Api\Http\Controllers\PricingPlanController;
use Liberu\Billing\Pricing\Api\Http\Controllers\PricingSupportController;

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.pricing.read'])->prefix('api/v1/billing/pricing')->name('billing.pricing.')->group(function (): void {
    Route::get('/contracts', [PricingSupportController::class, 'contracts'])->name('contracts.index');
    Route::get('/discounts', [PricingSupportController::class, 'discounts'])->name('discounts.index');
    Route::get('/snapshots', [PricingSupportController::class, 'snapshots'])->name('snapshots.index');
    Route::post('/proration', [PricingSupportController::class, 'proration'])->name('proration');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.pricing.write', 'idempotency'])->prefix('api/v1/billing/pricing')->name('billing.pricing.')->group(function (): void {
    Route::post('/discounts/{discount}/redeem', [PricingSupportController::class, 'redeem'])->name('discounts.redeem');
    Route::post('/plans/{plan}/snapshot', [PricingSupportController::class, 'snapshot'])->name('plans.snapshot');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.pricing.read'])->prefix('api/v1/billing/pricing')->name('billing.pricing.')->group(fn () => Route::get('/', [PricingPlanController::class, 'index'])->name('plans.index'));
Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.pricing.write', 'idempotency'])->prefix('api/v1/billing/pricing')->name('billing.pricing.')->group(fn () => Route::post('/', [PricingPlanController::class, 'store'])->name('plans.store'));
