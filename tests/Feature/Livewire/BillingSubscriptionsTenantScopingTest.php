<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Billing\Subscriptions\Actions\CancelSubscription;
use Liberu\Billing\Subscriptions\Livewire\Components\SubscriptionList;
use Liberu\Billing\Subscriptions\Models\Subscription;
use Tests\TestCase;

class BillingSubscriptionsTenantScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_mutation_cannot_access_another_team_subscription(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->update(['current_team_id' => $user->currentTeam->id]);
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $subscription = Subscription::query()->create([
            'team_id' => $otherTeam->id,
            'status' => 'active',
            'starts_at' => now(),
            'auto_renew' => true,
            'entitlement_state' => [],
        ]);
        $component = app(SubscriptionList::class);

        expect(fn () => $component->cancel($subscription->getKey(), app(CancelSubscription::class)))
            ->toThrow(ModelNotFoundException::class);

        $this->assertDatabaseHas('billing_subscriptions', [
            'id' => $subscription->getKey(),
            'team_id' => $otherTeam->id,
            'status' => 'active',
        ]);
    }
}
