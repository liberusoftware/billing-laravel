<?php

namespace Tests\Unit\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\PaymentPlan;
use App\Services\BillingService;
use App\Services\PartialPaymentService;
use App\Services\PaymentGatewayService;
use App\Services\PaymentPlanService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartialPaymentPlanTest extends TestCase
{
    use RefreshDatabase;

    private function gateway(): PaymentGateway
    {
        return PaymentGateway::create([
            'name' => 'Test Gateway',
            'api_key' => 'key',
            'secret_key' => 'secret',
        ]);
    }

    /**
     * Build a PartialPaymentService whose gateway always reports success,
     * so the deterministic record-keeping logic can be exercised in isolation.
     */
    private function partialPaymentServiceWithSuccessfulGateway(): PartialPaymentService
    {
        $this->mock(PaymentGatewayService::class, function ($mock): void {
            $mock->shouldReceive('processPayment')
                ->andReturn(['success' => true, 'transaction_id' => 'txn-ppp-1']);
        });

        return new PartialPaymentService(app(PaymentGatewayService::class));
    }

    // ---------------------------------------------------------------------
    // Partial payments
    // ---------------------------------------------------------------------

    /**
     * A successful partial payment persists the Payment (with payment_method)
     * and transitions the invoice to 'partially_paid' (now a valid status
     * enum value, see add_partially_paid_to_invoices_status_enum migration).
     */
    public function test_partial_payment_persists_and_marks_invoice_partially_paid(): void
    {
        $gateway = $this->gateway();
        $invoice = Invoice::factory()->create([
            'total_amount' => 100.00,
            'status' => 'pending',
        ]);

        $result = $this->partialPaymentServiceWithSuccessfulGateway()
            ->processPartialPayment($invoice, 40.00, $gateway->id);

        $this->assertTrue($result['success']);
        $this->assertDatabaseCount('payments', 1);
        $this->assertEquals(40.00, (float) $result['payment']->amount);
        $this->assertEquals('partially_paid', $invoice->fresh()->status);
    }

    public function test_remaining_amount_reflects_recorded_payments(): void
    {
        $gateway = $this->gateway();
        $invoice = Invoice::factory()->create([
            'total_amount' => 100.00,
            'status' => 'pending',
        ]);

        $this->assertEquals(100.00, (float) $invoice->remaining_amount);

        Payment::create([
            'invoice_id' => $invoice->id,
            'payment_gateway_id' => $gateway->id,
            'amount' => 30.00,
            'currency' => $invoice->currency,
            'payment_method' => 'credit card',
            'transaction_id' => 'txn-a',
            'payment_date' => now(),
        ]);
        $this->assertEquals(70.00, (float) $invoice->fresh()->remaining_amount);

        Payment::create([
            'invoice_id' => $invoice->id,
            'payment_gateway_id' => $gateway->id,
            'amount' => 25.00,
            'currency' => $invoice->currency,
            'payment_method' => 'credit card',
            'transaction_id' => 'txn-b',
            'payment_date' => now(),
        ]);
        $this->assertEquals(45.00, (float) $invoice->fresh()->remaining_amount);
    }

    /**
     * BUG: the amount guard (PartialPaymentService:16-18) sits BEFORE
     * DB::beginTransaction()/try (lines 20-22), so an invalid amount throws an
     * uncaught Exception instead of returning the ['success'=>false] failure
     * array the catch block produces for every other error.
     */
    public function test_partial_payment_exceeding_remaining_returns_failure(): void
    {
        $gateway = $this->gateway();
        $invoice = Invoice::factory()->create([
            'total_amount' => 100.00,
            'status' => 'pending',
        ]);

        $service = new PartialPaymentService(app(PaymentGatewayService::class));

        $result = $service->processPartialPayment($invoice, 150.00, $gateway->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid partial payment amount', $result['message']);
    }

    public function test_partial_payment_of_zero_returns_failure(): void
    {
        $gateway = $this->gateway();
        $invoice = Invoice::factory()->create([
            'total_amount' => 100.00,
            'status' => 'pending',
        ]);

        $service = new PartialPaymentService(app(PaymentGatewayService::class));

        $result = $service->processPartialPayment($invoice, 0.0, $gateway->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid partial payment amount', $result['message']);
    }

    /**
     * BillingService::handlePartialPayment now delegates to an injected
     * PartialPaymentService (which uses the injected PaymentGatewayService), so
     * a mocked gateway flows through and the entry point is unit-testable.
     */
    public function test_handle_partial_payment_processes_via_injected_gateway(): void
    {
        $this->mock(PaymentGatewayService::class, function ($mock): void {
            $mock->shouldReceive('processPayment')
                ->andReturn(['success' => true, 'transaction_id' => 'txn-hpp']);
        });

        $gateway = $this->gateway();
        $invoice = Invoice::factory()->create([
            'total_amount' => 100.00,
            'status' => 'pending',
        ]);

        $result = app(BillingService::class)->handlePartialPayment($invoice, 40.00, $gateway->id);

        $this->assertTrue($result['success']);
        $this->assertEquals('partially_paid', $invoice->fresh()->status);
    }

    // ---------------------------------------------------------------------
    // Payment plans (BillingService::setupPaymentPlan -> Invoice::createPaymentPlan)
    // ---------------------------------------------------------------------

    public function test_setup_payment_plan_splits_invoice_into_equal_installments(): void
    {
        Carbon::setTestNow('2026-06-26');

        $invoice = Invoice::factory()->create([
            'total_amount' => 120.00,
            'is_installment' => false,
        ]);

        $billingService = app(BillingService::class);
        $plan = $billingService->setupPaymentPlan($invoice, 3, 'monthly');

        $this->assertInstanceOf(PaymentPlan::class, $plan);
        $this->assertEquals(3, $plan->total_installments);
        $this->assertEquals(40.00, (float) $plan->installment_amount);
        $this->assertEquals('monthly', $plan->frequency);
        $this->assertEquals('active', $plan->status);
        // next_due_date one month after the start date (now).
        $this->assertEquals('2026-07-26', $plan->next_due_date->format('Y-m-d'));

        Carbon::setTestNow();
    }

    public function test_setup_payment_plan_rounds_installment_amount(): void
    {
        $invoice = Invoice::factory()->create([
            'total_amount' => 100.00,
            'is_installment' => false,
        ]);

        $plan = app(BillingService::class)->setupPaymentPlan($invoice, 3, 'monthly');

        // round(100/3, 2) = 33.33; note 33.33 * 3 = 99.99 (rounding shortfall).
        $this->assertEquals(33.33, (float) $plan->installment_amount);
    }

    public function test_setup_payment_plan_twice_throws(): void
    {
        $invoice = Invoice::factory()->create([
            'total_amount' => 90.00,
            'is_installment' => false,
        ]);

        $billingService = app(BillingService::class);
        $billingService->setupPaymentPlan($invoice, 3, 'monthly');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invoice already has a payment plan');
        $billingService->setupPaymentPlan($invoice->fresh(), 3, 'monthly');
    }

    public function test_cannot_create_payment_plan_on_installment_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'total_amount' => 90.00,
            'is_installment' => true,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot create payment plan for an installment invoice');
        app(BillingService::class)->setupPaymentPlan($invoice, 3, 'monthly');
    }

    // ---------------------------------------------------------------------
    // PaymentPlanService::createInstallmentInvoice / processPaymentPlans
    // ---------------------------------------------------------------------

    public function test_create_installment_invoice_generates_child_invoice_and_advances_due_date(): void
    {
        Carbon::setTestNow('2026-06-26');

        $invoice = Invoice::factory()->create([
            'total_amount' => 120.00,
            'invoice_number' => 'INV-PLAN',
            'is_installment' => false,
        ]);
        $plan = app(BillingService::class)->setupPaymentPlan($invoice, 3, 'monthly');

        $originalNextDue = $plan->next_due_date->copy();

        $installment = app(PaymentPlanService::class)->createInstallmentInvoice($plan->fresh());

        $this->assertTrue((bool) $installment->is_installment);
        $this->assertEquals($invoice->id, $installment->parent_invoice_id);
        $this->assertEquals(40.00, (float) $installment->total_amount);
        $this->assertEquals('pending', $installment->status);
        $this->assertEquals('INV-PLAN-INST1', $installment->invoice_number);

        // Child invoice is discoverable via the parent invoice relationship.
        $this->assertEquals(1, $invoice->fresh()->installments()->count());

        // The plan's next due date advanced by one month.
        $this->assertEquals(
            $originalNextDue->copy()->addMonth()->format('Y-m-d'),
            $plan->fresh()->next_due_date->format('Y-m-d')
        );

        Carbon::setTestNow();
    }

    public function test_process_payment_plans_creates_installment_for_due_plans(): void
    {
        Carbon::setTestNow('2026-06-26');

        $invoice = Invoice::factory()->create([
            'total_amount' => 120.00,
            'is_installment' => false,
        ]);
        $plan = app(BillingService::class)->setupPaymentPlan($invoice, 3, 'monthly');

        // Force the plan to be due now.
        $plan->update(['next_due_date' => Carbon::now()->subDay()]);

        app(BillingService::class)->processPaymentPlans();

        $this->assertEquals(1, $invoice->fresh()->installments()->count());

        Carbon::setTestNow();
    }

    public function test_payment_plan_installments_relationship_matches_child_invoices(): void
    {
        // Create a throwaway invoice first so the plan id and parent invoice id
        // diverge — proves the relationship keys on invoice_id, not the plan id.
        Invoice::factory()->create(['is_installment' => false]);

        $invoice = Invoice::factory()->create([
            'total_amount' => 120.00,
            'is_installment' => false,
        ]);
        $plan = app(BillingService::class)->setupPaymentPlan($invoice, 3, 'monthly');

        // Guard: ids actually differ, otherwise this test proves nothing.
        $this->assertNotEquals($plan->id, $invoice->id);

        app(PaymentPlanService::class)->createInstallmentInvoice($plan->fresh());

        // PaymentPlan::installments() keys parent_invoice_id against the plan's
        // invoice_id, so it now finds the child installment invoice.
        $this->assertEquals(1, $invoice->fresh()->installments()->count());
        $this->assertEquals(1, $plan->fresh()->installments()->count());
    }
}
