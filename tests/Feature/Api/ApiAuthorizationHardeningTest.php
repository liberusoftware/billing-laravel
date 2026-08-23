<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Client;
use App\Models\ClientContact;
use App\Models\ClientNote;
use App\Models\Customer;
use App\Models\PackageGroup;
use App\Models\User;
use App\Models\WebhookEndpoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAuthorizationHardeningTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        Gate::before(fn (): true => true); // isolate tenancy from Shield permissions
    }

    private function otherTeamId(): int
    {
        return User::factory()->withPersonalTeam()->create()->currentTeam->id;
    }

    public function test_cannot_view_another_teams_package_group(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $group = PackageGroup::create(['team_id' => $this->otherTeamId(), 'name' => 'Theirs']);

        $this->getJson("/api/package-groups/{$group->id}")->assertStatus(404);
    }

    public function test_package_group_store_forces_callers_team(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $otherTeam = $this->otherTeamId();

        $response = $this->postJson('/api/package-groups', [
            'name' => 'Mine',
            'team_id' => $otherTeam, // attacker-supplied id must be ignored
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('package_groups', [
            'name' => 'Mine',
            'team_id' => $this->user->currentTeam->id,
        ]);
    }

    public function test_package_group_routes_require_ability(): void
    {
        Sanctum::actingAs($this->user, ['invoices:read']); // wrong ability
        $this->getJson('/api/package-groups')->assertStatus(403);
    }

    public function test_cannot_view_another_teams_customer_contact(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $customer = Customer::factory()->create(['team_id' => $this->otherTeamId()]);
        $contact = ClientContact::create([
            'customer_id' => $customer->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
        ]);

        $this->getJson("/api/customers/{$customer->id}/contacts")->assertStatus(404);
        $this->getJson("/api/customers/{$customer->id}/contacts/{$contact->id}")->assertStatus(404);
    }

    public function test_client_note_index_requires_read_ability(): void
    {
        Sanctum::actingAs($this->user, ['client-notes:write']); // write only, no read
        $this->getJson('/api/client-notes?client_id=1')->assertStatus(403);
    }

    public function test_cannot_delete_another_users_client_note(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $otherUser = User::factory()->create();
        $client = Client::create(['name' => 'C', 'email' => 'c@example.com']);
        $note = ClientNote::create([
            'client_id' => $client->id,
            'user_id' => $otherUser->id,
            'content' => 'secret note',
        ]);

        $this->deleteJson("/api/client-notes/{$note->id}")->assertStatus(404);
        $this->assertDatabaseHas('client_notes', ['id' => $note->id]);
    }

    public function test_client_note_index_only_returns_callers_own_notes(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $otherUser = User::factory()->create();
        $client = Client::create(['name' => 'C', 'email' => 'c@example.com']);
        ClientNote::create(['client_id' => $client->id, 'user_id' => $otherUser->id, 'content' => 'theirs']);

        $response = $this->getJson("/api/client-notes?client_id={$client->id}");

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    public function test_webhook_secret_is_never_serialized(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $endpoint = WebhookEndpoint::create([
            'team_id' => $this->user->currentTeam->id,
            'url' => 'https://example.com/hook',
            'secret' => 'super-secret-value',
            'is_active' => true,
        ]);

        $index = $this->getJson('/api/webhooks');
        $index->assertStatus(200);
        $this->assertStringNotContainsString('super-secret-value', $index->getContent());
        $this->assertArrayNotHasKey('secret', $index->json('data.0'));

        $show = $this->getJson("/api/webhooks/{$endpoint->id}");
        $show->assertStatus(200);
        $this->assertStringNotContainsString('super-secret-value', $show->getContent());
    }

    public function test_cannot_create_invoice_for_another_teams_customer(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $foreignCustomer = Customer::factory()->create(['team_id' => $this->otherTeamId()]);

        $this->postJson('/api/invoices', [
            'customer_id' => $foreignCustomer->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'items' => [
                ['description' => 'Test', 'quantity' => 1, 'price' => 100.00],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors(['customer_id']);
    }
}
