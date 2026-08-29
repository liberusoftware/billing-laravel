<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Liberu\Billing\Pricing\Models\PricingDiscount;
use Liberu\Billing\Pricing\Models\PricingPlan;
use Tests\TestCase;

class BillingPricingTenantScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_discount_redemption_cannot_access_another_team_discount(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $discount = PricingDiscount::query()->create([
            'team_id' => $otherTeam->id,
            'code' => 'OTHER-TEAM',
            'kind' => 'fixed',
            'value' => 100,
            'active' => true,
        ]);
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/billing/pricing/discounts/'.$discount->getKey().'/redeem')
            ->assertNotFound();

        $this->assertDatabaseHas('billing_pricing_discounts', [
            'id' => $discount->getKey(),
            'team_id' => $otherTeam->id,
            'redemptions' => 0,
        ]);
    }

    public function test_pricing_snapshot_cannot_access_another_team_plan(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $plan = PricingPlan::query()->create([
            'team_id' => $otherTeam->id,
            'name' => 'Other team plan',
            'pricing_model' => 'one_time',
            'currency' => 'USD',
            'unit_amount_minor' => 100,
            'status' => 'draft',
        ]);
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/billing/pricing/plans/'.$plan->getKey().'/snapshot')
            ->assertNotFound();

        $this->assertDatabaseMissing('billing_pricing_snapshots', [
            'pricing_plan_id' => $plan->getKey(),
        ]);
    }
}
