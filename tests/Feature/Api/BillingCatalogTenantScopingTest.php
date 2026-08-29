<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Liberu\Billing\Catalog\Models\Plan;
use Tests\TestCase;

class BillingCatalogTenantScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_record_lifecycle_cannot_access_another_team_record(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $plan = Plan::query()->create([
            'team_id' => $otherTeam->id,
            'name' => 'Other team plan',
            'code' => 'OTHER-PLAN',
            'status' => 'draft',
        ]);
        Sanctum::actingAs($user, ['*']);

        $this->patchJson('/api/v1/billing/catalog/plans/'.$plan->getKey().'/lifecycle', [
            'status' => 'active',
        ])->assertNotFound();

        $this->assertDatabaseHas('billing_catalog_plans', [
            'id' => $plan->getKey(),
            'team_id' => $otherTeam->id,
            'status' => 'draft',
        ]);
    }
}
