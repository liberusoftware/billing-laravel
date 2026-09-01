<?php

use Illuminate\Support\Facades\Route;
use Liberu\Billing\Provisioning\Api\Http\Controllers\ProvisioningOperationController;

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.provisioning.read'])->prefix('api/v1/billing/provisioning/operations')->group(function (): void {
    Route::get('/', [ProvisioningOperationController::class, 'index'])->name('billing.provisioning.operations.index');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.provisioning.write', 'idempotency'])->prefix('api/v1/billing/provisioning')->group(function (): void {
    Route::post('/', [ProvisioningOperationController::class, 'storeService'])->name('billing.provisioning.store');
    Route::post('/{provisionedService}/operations', [ProvisioningOperationController::class, 'queue'])->name('billing.provisioning.operations.store');
    Route::post('/{provisionedService}/reconcile', [ProvisioningOperationController::class, 'reconcile'])->name('billing.provisioning.reconcile');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.provisioning.read'])->prefix('api/v1/billing/provisioning')->group(function (): void {
    Route::get('/', [ProvisioningOperationController::class, 'services'])->name('billing.provisioning.services.index');
    Route::get('/{provisionedService}', [ProvisioningOperationController::class, 'showService'])->whereNumber('provisionedService')->name('billing.provisioning.services.show');
});
