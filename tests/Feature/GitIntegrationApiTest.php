<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\GitConnection;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GitIntegrationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_create_connection_without_exposing_encrypted_secrets(): void
    {
        [, $team] = $this->operator();

        $response = $this->postJson('/api/git/connections', [
            'provider' => 'github',
            'name' => 'Production GitHub',
            'base_url' => 'https://api.github.com',
            'access_token' => 'trusted-access-token',
            'webhook_secret' => 'trusted-webhook-secret',
        ])->assertCreated()
            ->assertJsonPath('team_id', $team->id)
            ->assertJsonMissingPath('access_token')
            ->assertJsonMissingPath('webhook_secret');

        $connection = GitConnection::query()->findOrFail($response->json('id'));
        $this->assertNotSame('trusted-access-token', $connection->getRawOriginal('access_token'));
        $this->assertSame('trusted-access-token', $connection->access_token);
    }

    public function test_tenant_cannot_see_or_mutate_another_tenants_connection(): void
    {
        $this->operator();
        $other = GitConnection::factory()->create();

        $this->patchJson("/api/git/connections/{$other->id}", ['name' => 'Stolen'])->assertNotFound();
        $this->deleteJson("/api/git/connections/{$other->id}")->assertNotFound();
    }

    /** @return array{User, Team} */
    private function operator(): array
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $user->update(['current_team_id' => $team->id]);
        Sanctum::actingAs($user, ['git:read', 'git:write']);

        return [$user, $team];
    }
}
