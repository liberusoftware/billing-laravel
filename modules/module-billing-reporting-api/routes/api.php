<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Liberu\Billing\Reporting\Api\Http\Controllers\ReportingMetricController;
use Liberu\Billing\Reporting\Models\MetricSnapshot;

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.reporting.read'])->prefix('api/v1/billing/reporting/metrics')->group(function (): void {
    Route::get('/', [ReportingMetricController::class, 'index'])->name('billing.reporting.metrics.index');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.reporting.write', 'idempotency'])->prefix('api/v1/billing/reporting/metrics')->group(function (): void {
    Route::post('/', [ReportingMetricController::class, 'store'])->name('billing.reporting.metrics.store');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.reporting.read'])->prefix('api/v1/billing/reporting')->group(function (): void {
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
    })->whereNumber('record');
});
