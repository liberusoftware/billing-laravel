<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Liberu\Billing\Domains\Models\Domain;

Route::middleware(['auth:sanctum', 'ability:billing.domains.read'])->prefix('api/v1/billing/domains')->group(function (): void {
    Route::get('/', function (Request $request) {
        Gate::authorize('viewAny', Domain::class);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');

        return Domain::query()->forTeam((int) $teamId)->latest()->paginate($request->integer('per_page', 25));
    });
    Route::get('/{record}', function (Request $request, int $record): Domain {
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $model = Domain::query()->forTeam((int) $teamId)->findOrFail($record);
        Gate::authorize('view', $model);

        return $model;
    });
});
