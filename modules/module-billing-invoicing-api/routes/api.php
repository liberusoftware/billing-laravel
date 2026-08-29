<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\Billing\Invoicing\Api\Http\Controllers\InvoiceController;
use Liberu\Billing\Invoicing\Api\Http\Controllers\InvoiceSupportController;

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.invoicing.read'])->prefix('api/v1/billing/invoicing/support')->name('billing.invoicing.support.')->group(function (): void {
    Route::get('/', [InvoiceSupportController::class, 'index'])->name('index');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.invoicing.write', 'idempotency'])->prefix('api/v1/billing/invoicing/support')->name('billing.invoicing.support.')->group(function (): void {
    Route::post('/', [InvoiceSupportController::class, 'store'])->name('store');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.invoicing.read'])->prefix('api/v1/billing/invoicing')->name('billing.invoicing.')->group(function (): void {
    Route::get('/', [InvoiceController::class, 'index'])->name('index');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.invoicing.write', 'idempotency'])->prefix('api/v1/billing/invoicing')->name('billing.invoicing.')->group(function (): void {
    Route::post('/', [InvoiceController::class, 'store'])->name('store');
    Route::post('/schedules', [InvoiceController::class, 'schedule'])->name('schedule');
    Route::post('/schedules/{schedule}/run', [InvoiceController::class, 'runSchedule'])->name('schedule.run');
    Route::post('/payment-plans', [InvoiceController::class, 'paymentPlan'])->name('payment-plan.create');
    Route::post('/payment-plans/{paymentPlan}/run', [InvoiceController::class, 'runPaymentPlan'])->name('payment-plan.run');
    Route::post('/{invoice}/lines', [InvoiceController::class, 'line'])->name('line');
    Route::post('/{invoice}/finalize', [InvoiceController::class, 'finalize'])->name('finalize');
    Route::post('/{invoice}/adjustments', [InvoiceController::class, 'adjust'])->name('adjust');
    Route::post('/{invoice}/document', [InvoiceController::class, 'document'])->name('document');
    Route::post('/{invoice}/deliver', [InvoiceController::class, 'deliver'])->name('deliver');
});
