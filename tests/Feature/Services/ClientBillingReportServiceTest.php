<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\ClientBillingReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Reporting aggregates over a customer's invoices/payments. Everything is
 * scoped by customer_id, so a second customer's data must never bleed in.
 * getPaymentTrends() is intentionally untested here — it uses MySQL's
 * DATE_FORMAT(), which the sqlite test driver does not implement.
 */
class ClientBillingReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClientBillingReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-07-10 12:00:00'));
        $this->service = new ClientBillingReportService();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_payment_status_totals_invoiced_paid_outstanding_and_overdue(): void
    {
        $customer = Customer::factory()->create();

        $pendingFuture = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'total_amount' => 100,
            'status' => 'pending',
            'due_date' => '2026-08-01', // not yet due
        ]);
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'total_amount' => 200,
            'status' => 'pending',
            'due_date' => '2026-06-01', // past due
        ]);
        $paid = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'total_amount' => 50,
            'status' => 'paid',
            'due_date' => '2026-06-15',
        ]);

        Payment::factory()->create(['invoice_id' => $pendingFuture->id, 'amount' => 30]);
        Payment::factory()->create(['invoice_id' => $paid->id, 'amount' => 50]);

        // A different customer's invoice + payment must be excluded from every total.
        $other = Customer::factory()->create();
        $otherInvoice = Invoice::factory()->create([
            'customer_id' => $other->id,
            'total_amount' => 999,
            'status' => 'pending',
            'due_date' => '2026-06-01',
        ]);
        Payment::factory()->create(['invoice_id' => $otherInvoice->id, 'amount' => 999]);

        $status = $this->service->getPaymentStatus($customer);

        $this->assertEquals(350, $status['total_invoiced']);   // 100 + 200 + 50
        $this->assertEquals(80, $status['total_paid']);        // 30 + 50
        $this->assertEquals(300, $status['total_outstanding']); // pending: 100 + 200
        $this->assertEquals(200, $status['overdue_amount']);    // pending & past-due: 200
    }

    public function test_billing_history_maps_paid_amount_and_balance_scoped_to_customer(): void
    {
        $customer = Customer::factory()->create();

        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-0001',
            'total_amount' => 100,
            'status' => 'pending',
            'due_date' => '2026-08-01',
        ]);
        Payment::factory()->create(['invoice_id' => $invoice->id, 'amount' => 30]);

        Invoice::factory()->create(['customer_id' => Customer::factory()->create()->id]);

        $history = $this->service->generateBillingHistory($customer);

        $this->assertCount(1, $history); // only this customer's invoice
        $row = $history->first();
        $this->assertSame('INV-0001', $row['invoice_number']);
        $this->assertEquals(30, $row['paid_amount']);
        $this->assertEquals(70, $row['balance']); // 100 - 30
        $this->assertSame('pending', $row['status']);
    }

    public function test_billing_history_start_date_filters_older_invoices(): void
    {
        $customer = Customer::factory()->create();

        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'created_at' => '2026-01-01',
            'due_date' => '2026-01-15',
        ]);
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-RECENT',
            'created_at' => '2026-07-01',
            'due_date' => '2026-07-15',
        ]);

        $history = $this->service->generateBillingHistory($customer, '2026-06-01');

        $this->assertCount(1, $history);
        $this->assertSame('INV-RECENT', $history->first()['invoice_number']);
    }
}
