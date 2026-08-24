<?php

use Illuminate\Support\Facades\Route;
use Liberu\Billing\Provisioning\Api\Http\Controllers\ProvisioningOperationController;
use Liberu\Billing\Provisioning\Models\ProvisionedService;

Route::middleware(['auth:sanctum', 'ability:billing.provisioning.read'])->prefix('api/v1/billing/provisioning/operations')->group(function (): void {
    Route::get('/', [ProvisioningOperationController::class, 'index'])->name('billing.provisioning.operations.index');
});

Route::middleware(['auth:sanctum', 'ability:billing.provisioning.write'])->prefix('api/v1/billing/provisioning')->group(function (): void {
    Route::post('/{provisionedService}/operations', [ProvisioningOperationController::class, 'queue'])->name('billing.provisioning.operations.store');
    Route::post('/{provisionedService}/reconcile', [ProvisioningOperationController::class, 'reconcile'])->name('billing.provisioning.reconcile');
});

Route::middleware('auth:sanctum')->prefix('api/v1/billing/provisioning')->group(function (): void {
    Route::get('/', fn () => ProvisionedService::query()->paginate());
    Route::get('/{provisionedService}', fn (ProvisionedService $provisionedService) => $provisionedService);
});
