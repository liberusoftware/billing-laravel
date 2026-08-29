<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Liberu\Billing\Invoicing\Actions\CreateInvoice;
use Tests\TestCase;

class BillingInvoicingTenantScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_mutations_cannot_access_another_team_invoice(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $invoice = app(CreateInvoice::class)->execute([
            'team_id' => $otherTeam->id,
            'currency' => 'USD',
        ]);
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/billing/invoicing/'.$invoice->getKey().'/finalize')
            ->assertNotFound();

        $this->assertDatabaseHas('billing_invoices', [
            'id' => $invoice->getKey(), 'team_id' => $otherTeam->id, 'status' => 'draft',
        ]);
    }
}
