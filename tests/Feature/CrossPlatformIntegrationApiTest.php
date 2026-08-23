<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ExternalConnection;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CrossPlatformIntegrationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_creates_connector_without_exposing_encrypted_credentials(): void
    {
        [, $team] = $this->operator();

        $response = $this->postJson('/api/integrations/connections', [
            'connector_type' => 'crm',
            'provider' => 'custom-crm',
            'name' => 'Sales CRM',
            'base_url' => 'https://crm.example.test/api',
            'access_token' => 'trusted-access-token',
            'signing_secret' => 'trusted-signing-secret',
            'resource_mappings' => ['customers' => '/accounts'],
        ])->assertCreated()
            ->assertJsonPath('team_id', $team->id)
            ->assertJsonMissingPath('access_token')
            ->assertJsonMissingPath('signing_secret');

        $connection = ExternalConnection::query()->findOrFail($response->json('id'));
        $this->assertNotSame('trusted-access-token', $connection->getRawOriginal('access_token'));
        $this->assertSame('trusted-access-token', $connection->access_token);
    }

    public function test_connector_access_is_tenant_isolated(): void
    {
        $this->operator();
        $foreign = ExternalConnection::factory()->create();

        $this->patchJson("/api/integrations/connections/{$foreign->id}", ['name' => 'Stolen'])
            ->assertNotFound();
        $this->deleteJson("/api/integrations/connections/{$foreign->id}")
            ->assertNotFound();
    }

    /** @return array{User, Team} */
    private function operator(): array
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $user->update(['current_team_id' => $team->id]);
        Sanctum::actingAs($user, ['integrations:read', 'integrations:write']);

        return [$user, $team];
    }
}
