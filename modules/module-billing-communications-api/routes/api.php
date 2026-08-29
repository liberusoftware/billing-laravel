<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Liberu\Billing\Communications\Api\Http\Controllers\CommunicationsController;
use Liberu\Billing\Communications\Models\CommunicationService;

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.communications.read'])->prefix('api/v1/billing/communications')->group(function (): void {
    Route::get('/voice/accounts', [CommunicationsController::class, 'voiceAccounts'])->name('billing.communications.voice.accounts.index');
    Route::get('/voice/accounts/{account}/cdrs', [CommunicationsController::class, 'callRecords'])->whereNumber('account')->name('billing.communications.voice.cdrs.index');
    Route::get('/voice/rates', [CommunicationsController::class, 'rates'])->name('billing.communications.voice.rates.index');
    Route::get('/', function (Request $request) {
        Gate::authorize('viewAny', CommunicationService::class);
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');

        return CommunicationService::query()->forTeam((int) $teamId)->latest()->paginate($request->integer('per_page', 25));
    });
    Route::get('/numbers', [CommunicationsController::class, 'numbers'])->name('numbers');
    Route::get('/providers', [CommunicationsController::class, 'providers'])->name('providers');
    Route::get('/usage-imports', [CommunicationsController::class, 'usageImports'])->name('usage-imports');
    Route::get('/{record}', function (Request $request, int $record): CommunicationService {
        $teamId = data_get($request->user(), 'current_team_id') ?? data_get($request->user(), 'currentTeam.id');
        $model = CommunicationService::query()->forTeam((int) $teamId)->findOrFail($record);
        Gate::authorize('view', $model);

        return $model;
    })->whereNumber('record');
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.communications.write', 'idempotency'])->prefix('api/v1/billing/communications')->group(function (): void {
    Route::post('/voice/accounts', [CommunicationsController::class, 'createVoiceAccount'])->name('billing.communications.voice.accounts.store');
    Route::post('/voice/accounts/{account}/provision', [CommunicationsController::class, 'provisionVoiceAccount'])->whereNumber('account')->name('billing.communications.voice.accounts.provision');
    Route::post('/voice/accounts/{account}/cdrs', [CommunicationsController::class, 'ingestCallRecord'])->whereNumber('account')->name('billing.communications.voice.cdrs.store');
    Route::post('/voice/rates', [CommunicationsController::class, 'createRate'])->name('billing.communications.voice.rates.store');
    Route::post('/', [CommunicationsController::class, 'createService'])->name('billing.communications.store');
    Route::patch('/{service}/lifecycle', [CommunicationsController::class, 'transitionService'])->whereNumber('service')->name('billing.communications.services.lifecycle');
    Route::post('/numbers', [CommunicationsController::class, 'provisionNumber'])->name('billing.communications.numbers.store');
    Route::patch('/numbers/{number}/status', [CommunicationsController::class, 'transitionNumber'])->whereNumber('number')->name('billing.communications.numbers.status');
    Route::post('/providers', [CommunicationsController::class, 'createProvider'])->name('billing.communications.providers.store');
    Route::post('/usage-imports', [CommunicationsController::class, 'importUsage'])->name('billing.communications.usage-imports.store');
});
