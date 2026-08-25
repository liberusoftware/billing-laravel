<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\Billing\Usage\Api\Http\Controllers\UsageController;

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.usage.read'])->prefix('api/v1/billing/usage')->name('billing.usage.')->group(function (): void {
    Route::get('/meters', [UsageController::class, 'meters'])->name('meters');
    Route::get('/meters/{meter}/aggregate', [UsageController::class, 'aggregateForMeter'])->name('aggregate');
    Route::post('/meters/{meter}/rate', [UsageController::class, 'rate'])->name('rate');
    Route::get('/records', [UsageController::class, 'records'])->name('records.index');
    Route::get('/aggregate', [UsageController::class, 'aggregate'])->name('records.aggregate');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.usage.write', 'idempotency'])->prefix('api/v1/billing/usage')->name('billing.usage.')->group(function (): void {
    Route::post('/meters', [UsageController::class, 'storeMeter'])->name('meters.store');
    Route::post('/meters/{meter}/records', [UsageController::class, 'ingest'])->name('records.store');
    Route::post('/meters/{meter}/ingest', [UsageController::class, 'ingest'])->name('records.ingest');
    Route::post('/records/{record}/correct', [UsageController::class, 'correct'])->name('records.correct');
    Route::post('/records/{record}/corrections', [UsageController::class, 'correct'])->name('records.corrected');
});
