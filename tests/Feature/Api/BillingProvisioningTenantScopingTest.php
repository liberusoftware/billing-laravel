<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Liberu\Billing\Provisioning\Models\ProvisionedService;
use Tests\TestCase;

class BillingProvisioningTenantScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_provisioning_mutations_cannot_access_another_team_service(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $service = ProvisionedService::query()->create([
            'team_id' => $otherTeam->id,
            'provider' => 'test',
            'state' => 'pending',
            'metadata' => [],
        ]);
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/billing/provisioning/'.$service->getKey().'/reconcile')
            ->assertNotFound();

        $this->assertDatabaseMissing('billing_provisioning_operations', [
            'provisioned_service_id' => $service->getKey(),
        ]);
    }
}
