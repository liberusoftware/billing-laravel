<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\Billing\Catalog\Api\Http\Controllers\CatalogRecordController;
use Liberu\Billing\Catalog\Api\Http\Controllers\ProductController;

$catalogTypes = 'plans|addons|bundles|options|eligibility|channels';

Route::middleware(['api', 'auth:sanctum', 'ability:billing.catalog.read'])
    ->prefix('api/v1/billing/catalog')->name('billing.catalog.')
    ->group(fn () => Route::get('/', [ProductController::class, 'index'])->name('products.index'));

Route::middleware(['api', 'auth:sanctum', 'ability:billing.catalog.write'])
    ->prefix('api/v1/billing/catalog')->name('billing.catalog.')
    ->group(fn () => Route::post('/', [ProductController::class, 'store'])->name('products.store'));

Route::middleware(['api', 'auth:sanctum', 'ability:billing.catalog.read'])
    ->prefix('api/v1/billing/catalog')->name('billing.catalog.')
    ->group(function () use ($catalogTypes): void {
        Route::get('/{type}', [CatalogRecordController::class, 'index'])->whereIn('type', explode('|', $catalogTypes))->name('records.index');
    });

Route::middleware(['api', 'auth:sanctum', 'ability:billing.catalog.write'])
    ->prefix('api/v1/billing/catalog')->name('billing.catalog.')
    ->group(function () use ($catalogTypes): void {
        Route::post('/{type}', [CatalogRecordController::class, 'store'])->whereIn('type', explode('|', $catalogTypes))->name('records.store');
        Route::patch('/{type}/{record}/lifecycle', [CatalogRecordController::class, 'transition'])->whereIn('type', explode('|', $catalogTypes))->name('records.lifecycle');
    });
