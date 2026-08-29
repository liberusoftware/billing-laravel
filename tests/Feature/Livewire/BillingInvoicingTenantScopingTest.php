<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Billing\Invoicing\Actions\FinalizeInvoice;
use Liberu\Billing\Invoicing\Livewire\Components\InvoiceList;
use Liberu\Billing\Invoicing\Models\Invoice;
use Tests\TestCase;

class BillingInvoicingTenantScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_mutation_cannot_access_another_team_invoice(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->update(['current_team_id' => $user->currentTeam->id]);
        $otherTeam = User::factory()->withPersonalTeam()->create()->currentTeam;
        $invoice = Invoice::query()->create([
            'team_id' => $otherTeam->id,
            'status' => 'draft',
            'currency' => 'USD',
            'subtotal_minor' => 0,
            'tax_minor' => 0,
            'total_minor' => 0,
        ]);

        $component = app(InvoiceList::class);

        expect(fn () => $component->finalize($invoice->getKey(), app(FinalizeInvoice::class)))
            ->toThrow(ModelNotFoundException::class);

        $this->assertDatabaseHas('billing_invoices', [
            'id' => $invoice->getKey(),
            'team_id' => $otherTeam->id,
            'status' => 'draft',
        ]);
    }
}
