<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Liberu\Billing\Reporting\Models\MetricSnapshot;

Route::middleware(['auth:sanctum', 'ability:billing.reporting.read'])->prefix('api/v1/billing/reporting')->group(function (): void {
    Route::get('/', function (Request $request) {
        Gate::authorize('viewAny', MetricSnapshot::class);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');

        return MetricSnapshot::query()->forTeam((int) $teamId)->latest()->paginate($request->integer('per_page', 25));
    });
    Route::get('/{record}', function (Request $request, int $record): MetricSnapshot {
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $model = MetricSnapshot::query()->forTeam((int) $teamId)->findOrFail($record);
        Gate::authorize('view', $model);

        return $model;
    });
});
