<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Quote;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTenantScopingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        Gate::before(fn (): true => true); // isolate tenancy from Shield permissions
        Sanctum::actingAs($this->user, ['*']);
    }

    public function test_cannot_view_another_teams_subscription(): void
    {
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $subscription = Subscription::factory()->create(['team_id' => $otherTeam->id]);

        $this->getJson("/api/subscriptions/{$subscription->id}")->assertStatus(404);
    }

    public function test_cannot_view_another_teams_quote(): void
    {
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $customer = Customer::factory()->create(['team_id' => $otherTeam->id]);
        $quote = Quote::create([
            'team_id' => $otherTeam->id,
            'customer_id' => $customer->id,
            'quote_number' => 'Q-X1',
            'title' => 'Theirs',
            'status' => 'draft',
            'currency' => 'USD',
        ]);

        $this->getJson("/api/quotes/{$quote->id}")->assertStatus(404);
    }

    public function test_cannot_transition_another_teams_quote(): void
    {
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $customer = Customer::factory()->create(['team_id' => $otherTeam->id]);
        $quote = Quote::create([
            'team_id' => $otherTeam->id,
            'customer_id' => $customer->id,
            'quote_number' => 'Q-X3',
            'title' => 'Theirs',
            'status' => 'sent',
            'currency' => 'USD',
        ]);

        $this->postJson("/api/quotes/{$quote->id}/accept")->assertStatus(404);
        $this->postJson("/api/quotes/{$quote->id}/send")->assertStatus(404);
        $this->assertEquals('sent', $quote->fresh()->status);
    }

    public function test_quote_statistics_only_reflect_own_team(): void
    {
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $customer = Customer::factory()->create(['team_id' => $otherTeam->id]);
        Quote::create([
            'team_id' => $otherTeam->id,
            'customer_id' => $customer->id,
            'quote_number' => 'Q-X2',
            'title' => 'Theirs',
            'status' => 'draft',
            'currency' => 'USD',
            'total' => 500,
        ]);

        // even if the attacker passes the other team's id, stats come from their own team
        $response = $this->getJson('/api/quotes-statistics?team_id='.$otherTeam->id);

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('data.draft.count'));
    }
}
