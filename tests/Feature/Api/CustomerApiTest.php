<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        Gate::before(fn (): true => true);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/customers')->assertStatus(401);
    }

    public function test_authenticated_user_can_list_customers(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        Customer::factory()->count(5)->create();

        $response = $this->getJson('/api/customers');

        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_create_customer(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson('/api/customers', [
            'name' => 'Test Customer',
            'email' => 'testcustomer@example.com',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('customers', ['email' => 'testcustomer@example.com']);
    }

    public function test_authenticated_user_can_view_customer(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $customer = Customer::factory()->create(['team_id' => $this->user->currentTeam->id]);

        $response = $this->getJson("/api/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $customer->id);
    }

    public function test_authenticated_user_can_update_customer(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $customer = Customer::factory()->create(['team_id' => $this->user->currentTeam->id]);

        $response = $this->putJson("/api/customers/{$customer->id}", [
            'name' => 'Updated Name',
            'email' => $customer->email,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Updated Name']);
    }

    public function test_authenticated_user_can_delete_customer(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $customer = Customer::factory()->create(['team_id' => $this->user->currentTeam->id]);

        $response = $this->deleteJson("/api/customers/{$customer->id}");

        $this->assertContains($response->getStatusCode(), [200, 204]);
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_customer_creation_requires_email(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson('/api/customers', ['name' => 'No Email']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_cannot_view_or_modify_another_teams_customer(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $theirs = Customer::factory()->create(['team_id' => $otherTeam->id]);

        $this->getJson("/api/customers/{$theirs->id}")->assertStatus(404);
        $this->putJson("/api/customers/{$theirs->id}", ['name' => 'Hack'])->assertStatus(404);
        $this->deleteJson("/api/customers/{$theirs->id}")->assertStatus(404);
        $this->assertDatabaseHas('customers', ['id' => $theirs->id, 'name' => $theirs->name]);
    }
}
