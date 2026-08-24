<?php

use Illuminate\Support\Facades\Route;
use Liberu\Billing\Provisioning\Models\ProvisionedService;

Route::middleware('auth:sanctum')->prefix('api/v1/billing/provisioning')->group(function (): void {
    Route::get('/', fn () => ProvisionedService::query()->paginate());
    Route::get('/{provisionedService}', fn (ProvisionedService $provisionedService) => $provisionedService);
});
