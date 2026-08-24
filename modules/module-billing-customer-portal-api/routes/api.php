<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Liberu\Billing\CustomerPortal\Api\Http\Controllers\PortalItemController;
use Liberu\Billing\CustomerPortal\Models\PortalRequest;

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.customer-portal.read'])->prefix('api/v1/billing/customer-portal/items')->group(function (): void {
    Route::get('/', [PortalItemController::class, 'index'])->name('billing.customer-portal.items.index');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.customer-portal.write', 'idempotency'])->prefix('api/v1/billing/customer-portal/items')->group(function (): void {
    Route::post('/', [PortalItemController::class, 'store'])->name('billing.customer-portal.items.store');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.customer-portal.read'])->prefix('api/v1/billing/customer-portal')->group(function (): void {
    Route::get('/', function (Request $request) {
        Gate::authorize('viewAny', PortalRequest::class);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');

        return PortalRequest::query()->forTeam((int) $teamId)->latest()->paginate($request->integer('per_page', 25));
    });
    Route::get('/{record}', function (Request $request, int $record): PortalRequest {
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $model = PortalRequest::query()->forTeam((int) $teamId)->findOrFail($record);
        Gate::authorize('view', $model);

        return $model;
    })->whereNumber('record');
});
