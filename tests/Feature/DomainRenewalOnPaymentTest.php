<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DomainRenewalOnPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function enomFake(): void
    {
        Http::fake([
            '*' => Http::response('<interface-response><ErrCount>0</ErrCount></interface-response>'),
        ]);
    }

    public function test_paid_domain_invoice_triggers_registry_renewal(): void
    {
        $this->enomFake();

        $subscription = Subscription::factory()->create([
            'domain_name' => 'example.com',
            'domain_registrar' => 'enom',
        ]);

        $invoice = Invoice::factory()->create(['subscription_id' => $subscription->id]);

        $invoice->markAsPaid();

        Http::assertSent(fn ($r): bool => str_contains($r->url(), 'command=Extend'));
    }

    public function test_renewal_is_idempotent_per_invoice(): void
    {
        $this->enomFake();

        $subscription = Subscription::factory()->create([
            'domain_name' => 'example.com',
            'domain_registrar' => 'enom',
        ]);

        $invoice = Invoice::factory()->create(['subscription_id' => $subscription->id]);

        $invoice->markAsPaid();
        $invoice->markAsPaid();

        Http::assertSentCount(1);
    }

    public function test_non_domain_subscription_is_skipped(): void
    {
        $this->enomFake();

        $subscription = Subscription::factory()->create([
            'domain_name' => null,
            'domain_registrar' => null,
        ]);

        $invoice = Invoice::factory()->create(['subscription_id' => $subscription->id]);

        $invoice->markAsPaid();

        Http::assertNothingSent();
    }
}
