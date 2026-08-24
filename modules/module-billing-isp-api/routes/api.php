<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Liberu\Billing\Isp\Models\AccessService;

Route::middleware(['auth:sanctum', 'ability:billing.isp.read'])->prefix('api/v1/billing/isp')->group(function (): void {
    Route::get('/', function (Request $request) {
        Gate::authorize('viewAny', AccessService::class);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');

        return AccessService::query()->forTeam((int) $teamId)->latest()->paginate($request->integer('per_page', 25));
    });
    Route::get('/{record}', function (Request $request, int $record): AccessService {
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $model = AccessService::query()->forTeam((int) $teamId)->findOrFail($record);
        Gate::authorize('view', $model);

        return $model;
    });
});
