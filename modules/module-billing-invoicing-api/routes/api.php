<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\Billing\Invoicing\Api\Http\Controllers\InvoiceController;
use Liberu\Billing\Invoicing\Api\Http\Controllers\InvoiceSupportController;

Route::middleware(['api', 'auth:sanctum', 'ability:billing.invoicing.read'])->prefix('api/v1/billing/invoicing/support')->name('billing.invoicing.support.')->group(function (): void {
    Route::get('/', [InvoiceSupportController::class, 'index'])->name('index');
});

Route::middleware(['api', 'auth:sanctum', 'ability:billing.invoicing.write'])->prefix('api/v1/billing/invoicing/support')->name('billing.invoicing.support.')->group(function (): void {
    Route::post('/', [InvoiceSupportController::class, 'store'])->name('store');
});

Route::middleware(['api', 'auth:sanctum', 'ability:billing.invoicing.read'])->prefix('api/v1/billing/invoicing')->name('billing.invoicing.')->group(function (): void {
    Route::get('/', [InvoiceController::class, 'index'])->name('index');
});

Route::middleware(['api', 'auth:sanctum', 'ability:billing.invoicing.write'])->prefix('api/v1/billing/invoicing')->name('billing.invoicing.')->group(function (): void {
    Route::post('/', [InvoiceController::class, 'store'])->name('store');
    Route::post('/{invoice}/lines', [InvoiceController::class, 'line'])->name('line');
    Route::post('/{invoice}/finalize', [InvoiceController::class, 'finalize'])->name('finalize');
});
