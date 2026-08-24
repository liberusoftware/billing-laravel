<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Liberu\Billing\Orders\Api\Http\Controllers\OrderController;

Route::middleware(['api', 'auth:sanctum', 'ability:billing.orders.read'])->prefix('api/v1/billing/orders')->name('billing.orders.')->group(fn () => Route::get('/', [OrderController::class, 'index'])->name('orders.index'));
Route::middleware(['api', 'auth:sanctum', 'ability:billing.orders.write'])->prefix('api/v1/billing/orders')->name('billing.orders.')->group(fn () => Route::post('/', [OrderController::class, 'store'])->name('orders.store'));
