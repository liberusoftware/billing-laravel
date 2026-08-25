<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Liberu\Billing\Provisioning\Api\Http\Controllers\ProvisioningOperationController;
use Liberu\Billing\Provisioning\Models\ProvisionedService;

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.provisioning.read'])->prefix('api/v1/billing/provisioning/operations')->group(function (): void {
    Route::get('/', [ProvisioningOperationController::class, 'index'])->name('billing.provisioning.operations.index');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.provisioning.write', 'idempotency'])->prefix('api/v1/billing/provisioning')->group(function (): void {
    Route::post('/', [ProvisioningOperationController::class, 'storeService'])->name('billing.provisioning.store');
    Route::post('/{provisionedService}/operations', [ProvisioningOperationController::class, 'queue'])->name('billing.provisioning.operations.store');
    Route::post('/{provisionedService}/reconcile', [ProvisioningOperationController::class, 'reconcile'])->name('billing.provisioning.reconcile');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.provisioning.read'])->prefix('api/v1/billing/provisioning')->group(function (): void {
    Route::get('/', function (Request $request) {
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');

        return ProvisionedService::query()->where('team_id', $teamId)->latest()->paginate($request->integer('per_page', 25));
    });
    Route::get('/{provisionedService}', function (Request $request, int $provisionedService): ProvisionedService {
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $service = ProvisionedService::query()->where('team_id', $teamId)->findOrFail($provisionedService);
        Gate::authorize('view', $service);

        return $service;
    })->whereNumber('provisionedService');
});
