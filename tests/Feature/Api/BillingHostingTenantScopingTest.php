<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Liberu\Billing\Hosting\Contracts\HostingDriver;
use Liberu\Billing\Hosting\Models\HostingAccount;
use Liberu\Billing\Hosting\Models\HostingCapability;
use Liberu\Billing\Hosting\Services\HostingDriverRegistry;
use Tests\TestCase;

class BillingHostingTenantScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_capability_lifecycle_cannot_access_another_team_capability(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->update(['current_team_id' => $user->currentTeam->id]);
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $capability = HostingCapability::query()->create([
            'team_id' => $otherTeam->id,
            'type' => 'plan',
            'name' => 'Other team plan',
            'status' => 'pending',
        ]);
        Sanctum::actingAs($user, ['*']);

        $this->patchJson('/api/v1/billing/hosting/capabilities/'.$capability->getKey().'/lifecycle', [
            'status' => 'active',
        ])->assertNotFound();
    }

    public function test_capability_cannot_attach_another_team_account(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->update(['current_team_id' => $user->currentTeam->id]);
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $account = HostingAccount::query()->create([
            'team_id' => $otherTeam->id,
            'name' => 'Other team account',
            'status' => 'active',
        ]);
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/billing/hosting/capabilities', [
            'type' => 'plan',
            'name' => 'Foreign account capability',
            'hosting_account_id' => $account->getKey(),
        ])->assertNotFound();

        $this->assertDatabaseMissing('billing_hosting_capabilities', [
            'name' => 'Foreign account capability',
        ]);
    }

    public function test_provider_operation_is_team_scoped(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->update(['current_team_id' => $user->currentTeam->id]);
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $account = HostingAccount::query()->create([
            'team_id' => $otherTeam->id,
            'name' => 'Other team hosting account',
            'status' => 'pending',
            'metadata' => ['driver' => 'test'],
        ]);
        app(HostingDriverRegistry::class)->register(new class() implements HostingDriver
        {
            public function key(): string
            {
                return 'test';
            }

            public function provision(array $attributes): array
            {
                return [];
            }

            public function suspend(array $attributes): array
            {
                return [];
            }

            public function terminate(array $attributes): array
            {
                return [];
            }
        });
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/billing/hosting/'.$account->getKey().'/operation', [
            'operation' => 'provision',
        ])->assertNotFound();
    }
}
