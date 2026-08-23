<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\PaymentHistory;
use App\Services\PaymentGatewayService;
use App\Services\PaymentReconciliationService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R2 — Payment + gateway E2E. Uses the Google Pay driver as a fake gateway:
 * its processGooglePayToken() is a self-contained placeholder (no external SDK),
 * so a charge runs end to end and triggers reconciliation without mocking HTTP.
 */
class PaymentGatewayE2ETest extends TestCase
{
    use RefreshDatabase;

    private function gateway(string $name): PaymentGateway
    {
        return PaymentGateway::create([
            'name' => $name,
            'api_key' => 'pk_test',
            'secret_key' => 'sk_test',
        ]);
    }

    public function test_charge_via_fake_gateway_completes_and_reconciles_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'total_amount' => 150.00,
            'currency' => 'USD',
            'status' => 'pending',
        ]);

        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'payment_gateway_id' => $this->gateway('Google Pay')->id,
            'payment_method' => 'google_pay',
            'google_pay_token' => 'gp_tok_visa',
            'amount' => 150.00,
            'currency' => 'USD',
        ]);

        $result = app(PaymentGatewayService::class)->processPayment($payment);

        $this->assertIsArray($result);

        $payment->refresh();
        $this->assertSame('completed', $payment->status);
        $this->assertStringStartsWith('gp_', $payment->transaction_id);
        $this->assertSame('reconciled', $payment->reconciliation_status);

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at, 'Reconciliation must stamp the invoice paid_at.');
    }

    public function test_unsupported_payment_method_throws(): void
    {
        $payment = Payment::factory()->create([
            'payment_gateway_id' => $this->gateway('Google Pay')->id,
            'payment_method' => 'bitcoin',
        ]);

        $this->expectException(Exception::class);

        try {
            app(PaymentGatewayService::class)->processPayment($payment);
        } catch (Exception $e) {
            $this->assertSame('Unsupported payment method: bitcoin', $e->getMessage());
            throw $e;
        }
    }

    public function test_reconciliation_flags_amount_discrepancy(): void
    {
        $invoice = Invoice::factory()->create([
            'total_amount' => 100.00,
            'currency' => 'USD',
            'status' => 'pending',
        ]);

        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 50.00,
            'currency' => 'USD',
        ]);

        $reconciled = app(PaymentReconciliationService::class)->reconcilePayment($payment);

        $this->assertFalse($reconciled);
        $this->assertSame('discrepancy', $payment->fresh()->reconciliation_status);
        $this->assertSame('pending', $invoice->fresh()->status, 'Discrepant payment must not mark the invoice paid.');
        $this->assertDatabaseHas(PaymentHistory::class, [
            'payment_id' => $payment->id,
            'status' => 'discrepancy',
        ]);
    }

    public function test_refund_via_gateway_returns_success(): void
    {
        $payment = Payment::factory()->create([
            'payment_gateway_id' => $this->gateway('PayPal')->id,
            'amount' => 80.00,
        ]);

        $result = app(PaymentGatewayService::class)->refundPayment($payment, 80.00);

        $this->assertTrue($result['success']);
    }
}
