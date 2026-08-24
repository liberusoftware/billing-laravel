<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Liberu\Billing\Communications\Api\Http\Controllers\CommunicationsController;
use Liberu\Billing\Communications\Models\CommunicationService;

Route::middleware(['auth:sanctum', 'ability:billing.communications.read'])->prefix('api/v1/billing/communications')->group(function (): void {
    Route::get('/', function (Request $request) {
        Gate::authorize('viewAny', CommunicationService::class);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');

        return CommunicationService::query()->forTeam((int) $teamId)->latest()->paginate($request->integer('per_page', 25));
    });
    Route::get('/{record}', function (Request $request, int $record): CommunicationService {
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $model = CommunicationService::query()->forTeam((int) $teamId)->findOrFail($record);
        Gate::authorize('view', $model);

        return $model;
    })->whereNumber('record');
    Route::get('/numbers', [CommunicationsController::class, 'numbers'])->name('numbers');
    Route::get('/providers', [CommunicationsController::class, 'providers'])->name('providers');
    Route::get('/usage-imports', [CommunicationsController::class, 'usageImports'])->name('usage-imports');
});

Route::middleware(['auth:sanctum', 'ability:billing.communications.write'])->prefix('api/v1/billing/communications')->group(function (): void {
    Route::post('/numbers', [CommunicationsController::class, 'provisionNumber'])->name('billing.communications.numbers.store');
    Route::post('/usage-imports', [CommunicationsController::class, 'importUsage'])->name('billing.communications.usage-imports.store');
});
