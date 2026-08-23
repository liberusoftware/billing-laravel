<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * A payment must only be reconciled against an invoice belonging to the same
 * customer — manual reconciliation across customers is a money-misallocation bug.
 */
class PaymentReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_reconcile_across_different_customers_is_rejected(): void
    {
        $customerA = Customer::factory()->create();
        $customerB = Customer::factory()->create();

        $payment = Payment::factory()->create([
            'customer_id' => $customerA->id,
            'amount' => 50.0,
            'currency' => 'USD',
        ]);

        $invoice = Invoice::factory()->create([
            'customer_id' => $customerB->id,
            'total_amount' => 50.0,
            'currency' => 'USD',
            'status' => 'pending',
        ]);

        $this->expectException(InvalidArgumentException::class);

        try {
            app(PaymentReconciliationService::class)->handleManualReconciliation($payment, $invoice);
        } finally {
            // Invoice must remain unpaid after a rejected reconciliation.
            $this->assertSame('pending', $invoice->refresh()->status);
        }
    }

    public function test_manual_reconcile_same_customer_marks_invoice_paid(): void
    {
        $customer = Customer::factory()->create();

        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'total_amount' => 75.0,
            'currency' => 'USD',
            'status' => 'pending',
        ]);

        $payment = Payment::factory()->create([
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'amount' => 75.0,
            'currency' => 'USD',
        ]);

        $reconciled = app(PaymentReconciliationService::class)->handleManualReconciliation($payment, $invoice);

        $this->assertTrue($reconciled);
        $this->assertSame('paid', $invoice->refresh()->status);
    }
}
