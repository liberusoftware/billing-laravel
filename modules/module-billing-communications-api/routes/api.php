<?php

use Illuminate\Support\Facades\Route;
use Liberu\Billing\Communications\Api\Http\Controllers\CommunicationsController;

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.communications.read'])->prefix('api/v1/billing/communications')->group(function (): void {
    Route::get('/voice/accounts', [CommunicationsController::class, 'voiceAccounts'])->name('billing.communications.voice.accounts.index');
    Route::get('/voice/accounts/{account}/cdrs', [CommunicationsController::class, 'callRecords'])->whereNumber('account')->name('billing.communications.voice.cdrs.index');
    Route::get('/voice/rates', [CommunicationsController::class, 'rates'])->name('billing.communications.voice.rates.index');
    Route::get('/', [CommunicationsController::class, 'index'])->name('billing.communications.index');
    Route::get('/numbers', [CommunicationsController::class, 'numbers'])->name('numbers');
    Route::get('/providers', [CommunicationsController::class, 'providers'])->name('providers');
    Route::get('/usage-imports', [CommunicationsController::class, 'usageImports'])->name('usage-imports');
    Route::get('/{record}', [CommunicationsController::class, 'show'])->whereNumber('record')->name('billing.communications.show');
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
