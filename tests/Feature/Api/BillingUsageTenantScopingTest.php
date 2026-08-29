<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Liberu\Billing\Usage\Models\Meter;
use Tests\TestCase;

class BillingUsageTenantScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_meter_aggregate_cannot_access_another_team_meter(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $meter = Meter::query()->create([
            'team_id' => $otherTeam->id,
            'name' => 'Other team meter',
            'code' => 'OTHER-METER',
            'unit' => 'seat',
            'unit_price_minor' => 100,
            'currency' => 'USD',
            'active' => true,
        ]);
        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/billing/usage/meters/'.$meter->getKey().'/aggregate')
            ->assertNotFound();
    }

    public function test_meter_rate_cannot_access_another_team_meter(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $meter = Meter::query()->create([
            'team_id' => $otherTeam->id,
            'name' => 'Other team meter',
            'code' => 'OTHER-METER',
            'unit' => 'seat',
            'unit_price_minor' => 100,
            'currency' => 'USD',
            'active' => true,
        ]);
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/billing/usage/meters/'.$meter->getKey().'/rate', ['quantity' => 2])
            ->assertNotFound();
    }
}
