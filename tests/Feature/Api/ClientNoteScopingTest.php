<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Client;
use App\Models\ClientNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientNoteScopingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private int $teamId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->teamId = $this->user->currentTeam->id;
        Gate::before(fn (): true => true); // isolate tenancy from Shield permissions
    }

    private function otherTeamId(): int
    {
        return User::factory()->withPersonalTeam()->create()->currentTeam->id;
    }

    public function test_index_returns_notes_for_clients_in_callers_team_including_other_authors(): void
    {
        Sanctum::actingAs($this->user, ['client-notes:read']);
        $teammate = User::factory()->create();
        $client = Client::create(['team_id' => $this->teamId, 'name' => 'C', 'email' => 'c@example.com']);
        ClientNote::create(['client_id' => $client->id, 'user_id' => $teammate->id, 'content' => 'team note']);

        $response = $this->getJson("/api/client-notes?client_id={$client->id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_excludes_notes_for_clients_in_another_team(): void
    {
        Sanctum::actingAs($this->user, ['client-notes:read']);
        $foreignClient = Client::create(['team_id' => $this->otherTeamId(), 'name' => 'F', 'email' => 'f@example.com']);
        ClientNote::create(['client_id' => $foreignClient->id, 'user_id' => $this->user->id, 'content' => 'theirs']);

        $response = $this->getJson("/api/client-notes?client_id={$foreignClient->id}");

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    public function test_cannot_delete_note_whose_client_belongs_to_another_team(): void
    {
        Sanctum::actingAs($this->user, ['client-notes:write']);
        $foreignClient = Client::create(['team_id' => $this->otherTeamId(), 'name' => 'F', 'email' => 'f@example.com']);
        // Same author as the caller, but the client is in another team.
        $note = ClientNote::create(['client_id' => $foreignClient->id, 'user_id' => $this->user->id, 'content' => 'secret']);

        $this->deleteJson("/api/client-notes/{$note->id}")->assertStatus(404);
        $this->assertDatabaseHas('client_notes', ['id' => $note->id]);
    }

    public function test_can_delete_note_whose_client_belongs_to_callers_team(): void
    {
        Sanctum::actingAs($this->user, ['client-notes:write']);
        $client = Client::create(['team_id' => $this->teamId, 'name' => 'C', 'email' => 'c@example.com']);
        $note = ClientNote::create(['client_id' => $client->id, 'user_id' => $this->user->id, 'content' => 'mine']);

        $this->deleteJson("/api/client-notes/{$note->id}")->assertStatus(200);
        $this->assertDatabaseMissing('client_notes', ['id' => $note->id]);
    }

    public function test_store_rejects_client_in_another_team(): void
    {
        Sanctum::actingAs($this->user, ['client-notes:write']);
        $foreignClient = Client::create(['team_id' => $this->otherTeamId(), 'name' => 'F', 'email' => 'f@example.com']);

        $this->postJson('/api/client-notes', [
            'client_id' => $foreignClient->id,
            'content' => 'attempt',
        ])->assertStatus(422)->assertJsonValidationErrors(['client_id']);

        $this->assertDatabaseMissing('client_notes', ['content' => 'attempt']);
    }

    public function test_store_accepts_client_in_callers_team_and_stamps_author(): void
    {
        Sanctum::actingAs($this->user, ['client-notes:write']);
        $client = Client::create(['team_id' => $this->teamId, 'name' => 'C', 'email' => 'c@example.com']);

        $this->postJson('/api/client-notes', [
            'client_id' => $client->id,
            'content' => 'allowed',
        ])->assertStatus(200);

        $this->assertDatabaseHas('client_notes', [
            'client_id' => $client->id,
            'user_id' => $this->user->id,
            'content' => 'allowed',
        ]);
    }
}
