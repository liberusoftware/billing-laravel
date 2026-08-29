<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Liberu\Billing\Hosting\Api\Http\Controllers\HostingCapabilityController;
use Liberu\Billing\Hosting\Models\HostingAccount;

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.hosting.read'])->prefix('api/v1/billing/hosting/capabilities')->group(function (): void {
    Route::get('/', [HostingCapabilityController::class, 'index'])->name('billing.hosting.capabilities.index');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.hosting.write', 'idempotency'])->prefix('api/v1/billing/hosting/capabilities')->group(function (): void {
    Route::post('/', [HostingCapabilityController::class, 'store'])->name('billing.hosting.capabilities.store');
    Route::patch('/{capability}/lifecycle', [HostingCapabilityController::class, 'transition'])->name('billing.hosting.capabilities.lifecycle');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.hosting.write', 'idempotency'])->prefix('api/v1/billing/hosting')->group(function (): void {
    Route::post('/', [HostingCapabilityController::class, 'storeAccount'])->name('billing.hosting.store');
    Route::post('/{account}/operation', [HostingCapabilityController::class, 'operation'])->whereNumber('account')->name('billing.hosting.accounts.operation');
    Route::patch('/{account}/lifecycle', [HostingCapabilityController::class, 'transitionAccount'])->whereNumber('account')->name('billing.hosting.accounts.lifecycle');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.hosting.read'])->prefix('api/v1/billing/hosting')->group(function (): void {
    Route::get('/', function (Request $request) {
        Gate::authorize('viewAny', HostingAccount::class);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');

        return HostingAccount::query()->forTeam((int) $teamId)->latest()->paginate($request->integer('per_page', 25));
    });
    Route::get('/{record}', function (Request $request, int $record): HostingAccount {
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $model = HostingAccount::query()->forTeam((int) $teamId)->findOrFail($record);
        Gate::authorize('view', $model);

        return $model;
    })->whereNumber('record');
});
