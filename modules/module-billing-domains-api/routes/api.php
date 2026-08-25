<?php

use Illuminate\Support\Facades\Route;
use Liberu\Billing\Domains\Api\Http\Controllers\DomainController;

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.domains.read'])->prefix('api/v1/billing/domains')->group(function (): void {
    Route::get('/search', [DomainController::class, 'search']);
    Route::get('/tlds', [DomainController::class, 'tlds']);
    Route::get('/contacts', [DomainController::class, 'contacts']);
    Route::get('/epp-operations', [DomainController::class, 'eppOperations']);
    Route::get('/{domain}/dns', [DomainController::class, 'dns']);
    Route::get('/', [DomainController::class, 'index']);
    Route::get('/{domain}', [DomainController::class, 'show']);
});

Route::middleware(['api', 'throttle:api', 'auth:sanctum', 'ability:billing.domains.write', 'idempotency'])->prefix('api/v1/billing/domains')->group(function (): void {
    Route::post('/', [DomainController::class, 'store']);
    Route::post('/tlds', [DomainController::class, 'storeTld']);
    Route::post('/tlds/sync', [DomainController::class, 'syncTlds']);
    Route::patch('/{domain}', [DomainController::class, 'update']);
    Route::delete('/{domain}', [DomainController::class, 'destroy']);
    Route::post('/{domain}/register', [DomainController::class, 'register']);
    Route::post('/{domain}/renew', [DomainController::class, 'renew']);
    Route::post('/{domain}/transfer', [DomainController::class, 'transfer']);
    Route::post('/contacts', [DomainController::class, 'storeContact']);
    Route::post('/{domain}/dns', [DomainController::class, 'storeDns']);
    Route::post('/{domain}/redeem', [DomainController::class, 'redeem']);
});
