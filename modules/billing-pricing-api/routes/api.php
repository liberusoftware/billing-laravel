<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Liberu\Billing\Pricing\Api\Http\Controllers\PricingPlanController;

Route::middleware(['api', 'auth:sanctum', 'ability:billing.pricing.read'])->prefix('api/v1/billing/pricing')->name('billing.pricing.')->group(fn () => Route::get('/', [PricingPlanController::class, 'index'])->name('plans.index'));
Route::middleware(['api', 'auth:sanctum', 'ability:billing.pricing.write'])->prefix('api/v1/billing/pricing')->name('billing.pricing.')->group(fn () => Route::post('/', [PricingPlanController::class, 'store'])->name('plans.store'));
