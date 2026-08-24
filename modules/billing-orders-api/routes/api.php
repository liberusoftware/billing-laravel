<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;
use Liberu\Billing\Orders\Api\Http\Controllers\OrderController;

Route::middleware(['api', 'auth:sanctum', 'ability:billing.orders.read'])->prefix('api/v1/billing/orders')->name('billing.orders.')->group(fn () => Route::get('/', [OrderController::class, 'index'])->name('orders.index'));
Route::middleware(['api', 'auth:sanctum', 'ability:billing.orders.write'])->prefix('api/v1/billing/orders')->name('billing.orders.')->group(fn () => Route::post('/', [OrderController::class, 'store'])->name('orders.store'));

Route::middleware(['api', 'auth:sanctum', 'ability:billing.orders.write'])->prefix('api/v1/billing/orders')->name('billing.orders.')->group(function (): void {
    Route::post('/quotes', [OrderController::class, 'quote'])->name('quotes.store');
    Route::post('/carts', [OrderController::class, 'cart'])->name('carts.store');
    Route::post('/carts/{cart}/checkout', [OrderController::class, 'checkout'])->name('carts.checkout');
    Route::post('/{order}/fraud-review', [OrderController::class, 'fraud'])->name('fraud-review');
    Route::post('/{order}/change-orders', [OrderController::class, 'change'])->name('change-orders.store');
});
