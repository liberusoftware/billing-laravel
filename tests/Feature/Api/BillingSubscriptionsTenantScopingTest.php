<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Liberu\Billing\Subscriptions\Models\Subscription;
use Tests\TestCase;

class BillingSubscriptionsTenantScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_mutations_cannot_access_another_team_subscription(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $subscription = Subscription::query()->create([
            'team_id' => $otherTeam->id,
            'status' => 'active',
            'starts_at' => now(),
            'auto_renew' => true,
            'entitlement_state' => [],
        ]);
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/billing/subscriptions/'.$subscription->getKey().'/cancel')
            ->assertNotFound();

        $this->assertDatabaseHas('billing_subscriptions', [
            'id' => $subscription->getKey(), 'team_id' => $otherTeam->id, 'status' => 'active',
        ]);
    }

    public function test_subscription_show_is_team_scoped(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $subscription = Subscription::query()->create([
            'team_id' => $user->currentTeam->id,
            'status' => 'active',
            'starts_at' => now(),
            'auto_renew' => true,
            'entitlement_state' => [],
        ]);
        $user->update(['current_team_id' => $user->currentTeam->id]);
        Sanctum::actingAs($user, ['billing.subscriptions.read']);

        $this->getJson('/api/v1/billing/subscriptions/'.$subscription->getKey())
            ->assertOk()
            ->assertJsonPath('data.id', (string) $subscription->getKey())
            ->assertJsonPath('data.type', 'billing-subscriptions');
    }
}
