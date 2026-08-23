<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\Billing\Catalog\Api\Http\Controllers\ProductController;

Route::middleware(['api', 'auth:sanctum', 'ability:billing.catalog.read'])
    ->prefix('api/v1/billing/catalog')->name('billing.catalog.')
    ->group(fn () => Route::get('/', [ProductController::class, 'index'])->name('products.index'));

Route::middleware(['api', 'auth:sanctum', 'ability:billing.catalog.write'])
    ->prefix('api/v1/billing/catalog')->name('billing.catalog.')
    ->group(fn () => Route::post('/', [ProductController::class, 'store'])->name('products.store'));
