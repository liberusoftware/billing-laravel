<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoiceApiTest extends TestCase
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
        $response = $this->getJson('/api/invoices');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_invoices(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        Invoice::factory()->count(3)->create(['team_id' => $this->user->currentTeam->id]);

        $response = $this->getJson('/api/invoices');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'invoice_number', 'status'],
                ],
            ]);
    }

    public function test_authenticated_user_can_view_single_invoice(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $invoice = Invoice::factory()->create(['team_id' => $this->user->currentTeam->id]);

        $response = $this->getJson("/api/invoices/{$invoice->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $invoice->id);
    }

    public function test_authenticated_user_can_create_invoice(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $customer = Customer::factory()->create(['team_id' => $this->user->currentTeam->id]);

        $response = $this->postJson('/api/invoices', [
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'items' => [
                [
                    'description' => 'Test Service',
                    'quantity' => 1,
                    'price' => 100.00,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.customer_id', $customer->id);

        $this->assertDatabaseHas('invoices', ['customer_id' => $customer->id]);
    }

    public function test_invoice_creation_validates_required_fields(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson('/api/invoices', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id', 'issue_date', 'due_date', 'items']);
    }

    public function test_invoice_pagination_works(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        Invoice::factory()->count(20)->create(['team_id' => $this->user->currentTeam->id]);

        $response = $this->getJson('/api/invoices?per_page=10');

        $response->assertStatus(200);
        $this->assertCount(10, $response->json('data'));
    }

    public function test_invoices_can_be_filtered_by_status(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $teamId = $this->user->currentTeam->id;
        Invoice::factory()->create(['status' => 'paid', 'team_id' => $teamId]);
        Invoice::factory()->create(['status' => 'pending', 'team_id' => $teamId]);

        $response = $this->getJson('/api/invoices?status=paid');

        $response->assertStatus(200);
        foreach ($response->json('data') as $invoice) {
            $this->assertEquals('paid', $invoice['status']);
        }
    }

    public function test_cannot_view_another_teams_invoice(): void
    {
        Sanctum::actingAs($this->user, ['*']);
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $theirs = Invoice::factory()->create(['team_id' => $otherTeam->id]);

        $this->getJson("/api/invoices/{$theirs->id}")->assertStatus(404);
    }
}
