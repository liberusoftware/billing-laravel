<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Liberu\Billing\Hosting\Models\HostingAccount;

Route::middleware(['auth:sanctum', 'ability:billing.hosting.read'])->prefix('api/v1/billing/hosting')->group(function (): void {
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
    });
});
