<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceDispute;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\DisputeService;
use App\Services\PaymentGatewayService;
use App\Services\RefundService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class RefundDisputeTest extends TestCase
{
    use RefreshDatabase;

    private function makePayment(array $overrides = []): Payment
    {
        $gateway = PaymentGateway::create([
            'name' => 'Test Gateway',
            'api_key' => 'key',
            'secret_key' => 'secret',
        ]);

        $invoice = Invoice::factory()->create(array_merge([
            'total_amount' => 100,
            'status' => 'paid',
        ], $overrides['invoice'] ?? []));

        return Payment::factory()->create(array_merge([
            'invoice_id' => $invoice->id,
            'payment_gateway_id' => $gateway->id,
            'amount' => 100,
            'currency' => 'USD',
            'refund_status' => 'none',
            'refunded_amount' => 0,
        ], $overrides['payment'] ?? []));
    }

    private function refundServiceWith(array $gatewayResult): RefundService
    {
        $gateway = Mockery::mock(PaymentGatewayService::class);
        $gateway->shouldReceive('refundPayment')->andReturn($gatewayResult);

        return new RefundService($gateway);
    }

    // ---- RefundService ----

    public function test_full_refund_marks_payment_full_and_invoice_refunded(): void
    {
        $payment = $this->makePayment();
        $service = $this->refundServiceWith(['success' => true]);

        $result = $service->processRefund($payment, 100.0);

        $this->assertTrue($result['success']);
        $payment->refresh();
        $this->assertSame('full', $payment->refund_status);
        $this->assertEquals(100, $payment->refunded_amount);
        $this->assertSame('refunded', $payment->invoice->fresh()->status);
    }

    public function test_partial_refund_marks_payment_partial(): void
    {
        $payment = $this->makePayment();
        $service = $this->refundServiceWith(['success' => true]);

        $result = $service->processRefund($payment, 40.0);

        $this->assertTrue($result['success']);
        $payment->refresh();
        $this->assertSame('partial', $payment->refund_status);
        $this->assertEquals(40, $payment->refunded_amount);
        $this->assertSame('partially_paid', $payment->invoice->fresh()->status);
    }

    public function test_refund_exceeding_payment_amount_throws(): void
    {
        $payment = $this->makePayment();
        $service = $this->refundServiceWith(['success' => true]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Refund amount cannot exceed the original payment amount.');

        $service->processRefund($payment, 150.0);
    }

    public function test_non_refundable_payment_throws(): void
    {
        $payment = $this->makePayment(['payment' => ['refund_status' => 'full']]);
        $service = $this->refundServiceWith(['success' => true]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('This payment is not refundable.');

        $service->processRefund($payment, 10.0);
    }

    public function test_gateway_failure_returns_failure_and_does_not_mutate_payment(): void
    {
        $payment = $this->makePayment();
        $service = $this->refundServiceWith(['success' => false, 'message' => 'Card declined']);

        $result = $service->processRefund($payment, 50.0);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Card declined', $result['message']);

        $payment->refresh();
        $this->assertSame('none', $payment->refund_status);
        $this->assertEquals(0, $payment->refunded_amount);
    }

    // ---- DisputeService ----

    public function test_create_dispute_opens_dispute_and_marks_invoice_disputed(): void
    {
        Mail::fake();
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id, 'status' => 'pending']);

        $dispute = (new DisputeService)->createDispute($invoice, [
            'reason' => 'incorrect_charge',
            'description' => 'Charged twice',
        ]);

        $this->assertInstanceOf(InvoiceDispute::class, $dispute);
        $this->assertSame('open', $dispute->status);
        $this->assertSame('disputed', $invoice->fresh()->status);
    }

    public function test_create_dispute_on_already_disputed_invoice_throws(): void
    {
        Mail::fake();
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);
        InvoiceDispute::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'status' => 'open',
            'reason' => 'x',
            'description' => 'y',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invoice already has an active dispute');

        (new DisputeService)->createDispute($invoice, ['reason' => 'r', 'description' => 'd']);
    }

    public function test_resolving_dispute_sets_resolved_at_and_invoice_pending(): void
    {
        Mail::fake();
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id, 'status' => 'disputed']);
        $dispute = InvoiceDispute::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'status' => 'open',
            'reason' => 'x',
            'description' => 'y',
        ]);

        $updated = (new DisputeService)->updateDisputeStatus($dispute, 'resolved', 'Refunded customer');

        $this->assertSame('resolved', $updated->status);
        $this->assertNotNull($updated->resolved_at);
        $this->assertSame('Refunded customer', $updated->resolution_notes);
        $this->assertSame('pending', $invoice->fresh()->status);
    }

    public function test_rejecting_dispute_sets_resolved_at_but_leaves_invoice_disputed(): void
    {
        Mail::fake();
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id, 'status' => 'disputed']);
        $dispute = InvoiceDispute::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'status' => 'open',
            'reason' => 'x',
            'description' => 'y',
        ]);

        $updated = (new DisputeService)->updateDisputeStatus($dispute, 'rejected');

        $this->assertSame('rejected', $updated->status);
        $this->assertNotNull($updated->resolved_at);
        $this->assertSame('disputed', $invoice->fresh()->status);
    }

    public function test_add_message_persists_message_with_attachments(): void
    {
        Mail::fake();
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);
        $dispute = InvoiceDispute::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'status' => 'open',
            'reason' => 'x',
            'description' => 'y',
        ]);

        $message = (new DisputeService)->addMessage($dispute, [
            'message' => 'Please review',
            'attachments' => ['receipt.pdf', 'screenshot.png'],
        ]);

        $this->assertNotNull($message->id);
        $this->assertSame('Please review', $message->message);
        $this->assertSame(['receipt.pdf', 'screenshot.png'], $message->fresh()->attachments);
    }
}
