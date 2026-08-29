<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Liberu\Billing\Core\Models\BillingCurrency;

uses(RefreshDatabase::class);

it('converts currencies through the modular billing core API', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $user->update(['current_team_id' => $user->currentTeam->id]);
    BillingCurrency::query()->create(['team_id' => $user->currentTeam->id, 'code' => 'USD', 'name' => 'US Dollar', 'decimal_places' => 2, 'enabled' => true, 'exchange_rate' => 1]);
    BillingCurrency::query()->create(['team_id' => $user->currentTeam->id, 'code' => 'EUR', 'name' => 'Euro', 'decimal_places' => 2, 'enabled' => true, 'exchange_rate' => 0.9]);
    Sanctum::actingAs($user, ['billing.billing-core.read']);

    $this->postJson('/api/v1/billing/billing-core/currencies/convert', ['amount' => 10, 'from' => 'USD', 'to' => 'EUR'])
        ->assertOk()->assertJsonPath('data.amount', 9);
});
