<?php

use Illuminate\Support\Facades\Route;
use Liberu\Billing\Reporting\Api\Http\Controllers\ReportingController;

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.reporting.read'])->prefix('api/v1/billing/reporting/metrics')->group(function (): void {
    Route::get('/', [ReportingController::class, 'metrics'])->name('billing.reporting.metrics.index');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.reporting.write', 'idempotency'])->prefix('api/v1/billing/reporting/metrics')->group(function (): void {
    Route::post('/', [ReportingController::class, 'recordMetric'])->name('billing.reporting.metrics.store');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.reporting.read'])->prefix('api/v1/billing/reporting')->group(function (): void {
    Route::get('/', [ReportingController::class, 'snapshots'])->name('billing.reporting.snapshots.index');
    Route::get('/{record}', [ReportingController::class, 'snapshot'])->whereNumber('record')->name('billing.reporting.snapshots.show');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.reporting.write', 'idempotency'])->prefix('api/v1/billing/reporting')->group(function (): void {
    Route::post('/snapshots', [ReportingController::class, 'createSnapshot'])->name('billing.reporting.snapshots.store');
});
