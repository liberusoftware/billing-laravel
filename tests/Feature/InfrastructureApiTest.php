<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InfrastructureAsset;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InfrastructureApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_create_asset_pool_allocate_and_release_address(): void
    {
        [, $team] = $this->operator();

        $asset = $this->postJson('/api/infrastructure/assets', [
            'asset_type' => 'router',
            'name' => 'Core Router',
            'hostname' => 'core-1.example.test',
            'serial_number' => 'SERIAL-1',
        ])->assertCreated()->json();

        $pool = $this->postJson('/api/infrastructure/ip-pools', [
            'name' => 'Customer IPv4',
            'cidr' => '192.0.2.0/30',
            'gateway' => '192.0.2.1',
            'infrastructure_asset_id' => $asset['id'],
            'vlan_id' => 100,
        ])->assertCreated()
            ->assertJsonPath('team_id', $team->id)
            ->assertJsonPath('first_address', '192.0.2.1')
            ->json();

        $address = $this->postJson("/api/infrastructure/ip-pools/{$pool['id']}/allocate", [
            'target_type' => 'asset',
            'target_id' => $asset['id'],
            'hostname' => 'core-1.example.test',
        ])->assertCreated()
            ->assertJsonPath('address', '192.0.2.1')
            ->json();

        $this->postJson("/api/infrastructure/ip-addresses/{$address['id']}/release")
            ->assertOk()
            ->assertJsonPath('status', 'available')
            ->assertJsonPath('assignable_id', null);
    }

    public function test_tenant_cannot_use_another_tenants_assets_or_pools(): void
    {
        $this->operator();
        $otherAsset = InfrastructureAsset::factory()->create();

        $this->patchJson("/api/infrastructure/assets/{$otherAsset->id}", ['name' => 'Stolen'])
            ->assertNotFound();
        $this->postJson('/api/infrastructure/ip-pools', [
            'name' => 'Foreign',
            'cidr' => '10.10.0.0/24',
            'infrastructure_asset_id' => $otherAsset->id,
        ])->assertUnprocessable();
    }

    /** @return array{User, Team} */
    private function operator(): array
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $user->update(['current_team_id' => $team->id]);
        Sanctum::actingAs($user, ['infrastructure:read', 'infrastructure:write']);

        return [$user, $team];
    }
}
