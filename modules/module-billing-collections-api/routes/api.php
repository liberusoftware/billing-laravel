<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Liberu\Billing\Collections\Api\Http\Controllers\CollectionCaseController;

Route::middleware(['api', 'auth:sanctum', 'ability:billing.collections.read'])->prefix('api/v1/billing/collections')->name('billing.collections.')->group(function (): void {
    Route::get('/', [CollectionCaseController::class, 'index'])->name('index');
});

Route::middleware(['api', 'auth:sanctum', 'ability:billing.collections.write'])->prefix('api/v1/billing/collections')->name('billing.collections.')->group(function (): void {
    Route::post('/', [CollectionCaseController::class, 'store'])->name('store');
    Route::post('/{case}/promise', [CollectionCaseController::class, 'promise'])->name('promise');
    Route::post('/{case}/suspend', [CollectionCaseController::class, 'suspend'])->name('suspend');
    Route::post('/{case}/recover', [CollectionCaseController::class, 'recover'])->name('recover');
    Route::post('/{case}/retry', [CollectionCaseController::class, 'retry'])->name('retry');
    Route::post('/{case}/write-off', [CollectionCaseController::class, 'writeOff'])->name('write-off');
});
