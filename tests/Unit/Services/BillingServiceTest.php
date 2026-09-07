<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\DomainRenewal;
use App\Models\Invoice;
use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use App\Models\Products_Service;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\BillingService;
use App\Services\PaymentGatewayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BillingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $billingService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the PaymentGatewayService to avoid real payment processing
        $this->mock(PaymentGatewayService::class, function ($mock): void {
            $mock->shouldReceive('processPayment')
                ->andReturn(['success' => true, 'transaction_id' => 'test-txn-123']);
        });

        $this->billingService = app(BillingService::class);
    }

    public function test_generate_invoice(): void
    {
        $customer = Customer::factory()->create();
        $productService = Products_Service::factory()->create(['base_price' => 100]);
        $subscription = Subscription::factory()->create([
            'customer_id' => $customer->id,
            'product_service_id' => $productService->id,
        ]);

        $invoice = $this->billingService->generateInvoice($subscription);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($customer->id, $invoice->customer_id);
        $this->assertEquals(100, $invoice->total_amount);
        $this->assertEquals('pending', $invoice->status);
    }

    public function test_process_recurring_billing(): void
    {
        $subscription = Subscription::factory()->create([
            'end_date' => Carbon::yesterday(),
            'status' => 'active',
        ]);

        $this->billingService->processRecurringBilling();

        $this->assertDatabaseHas('invoices', [
            'customer_id' => $subscription->customer_id,
        ]);
    }

    public function test_recurring_plan_billing_uses_fixed_price_and_dispatches_domain_renewal_intent(): void
    {
        $subscription = Subscription::factory()->create([
            'end_date' => Carbon::yesterday(),
            'status' => 'active',
            'auto_renew' => true,
            'price' => 29.95,
            'currency' => 'USD',
            'domain_name' => 'renewal.example',
            'domain_registrar' => 'enom',
        ]);
        $gateway = PaymentGateway::create([
            'name' => 'Test Gateway',
            'api_key' => 'key',
            'secret_key' => 'secret',
        ]);
        PaymentMethod::create([
            'customer_id' => $subscription->customer_id,
            'payment_gateway_id' => $gateway->id,
            'type' => 'card',
            'is_default' => true,
        ]);

        $this->billingService->processRecurringBilling();

        $invoice = Invoice::query()->where('subscription_id', $subscription->id)->latest('id')->firstOrFail();
        $this->assertSame(29.95, (float) $invoice->total_amount);
        $this->assertSame('paid', $invoice->status);
        $this->assertTrue($invoice->is_recurring);
        $this->assertDatabaseHas('domain_renewals', [
            'invoice_id' => $invoice->id,
            'subscription_id' => $subscription->id,
            'domain' => 'renewal.example',
            'registrar' => 'enom',
            'status' => 'pending',
        ]);
        $this->assertInstanceOf(DomainRenewal::class, DomainRenewal::where('invoice_id', $invoice->id)->first());
    }

    public function test_failed_collection_does_not_create_a_second_invoice_for_the_same_renewal_period(): void
    {
        $subscription = Subscription::factory()->create([
            'end_date' => Carbon::yesterday(),
            'status' => 'active',
            'auto_renew' => true,
            'price' => 29.95,
        ]);

        $this->billingService->processRecurringBilling();
        $this->billingService->processRecurringBilling();

        $this->assertSame(1, Invoice::query()->where('subscription_id', $subscription->id)
            ->whereNotNull('renewal_period_end')->count());
    }

    public function test_upgrade_subscription_charges_prorated_difference(): void
    {
        Carbon::setTestNow('2026-07-11'); // 15 of 30 cycle days remaining

        $customer = Customer::factory()->create();
        $subscription = Subscription::factory()->create([
            'customer_id' => $customer->id,
            'price' => 10.00,
            'start_date' => Carbon::parse('2026-06-26'),
            'end_date' => Carbon::parse('2026-07-26'),
        ]);
        $newPlan = SubscriptionPlan::create([
            'name' => 'Pro',
            'code' => 'pro',
            'price' => 40.00,
        ]);

        $invoice = $this->billingService->upgradeSubscription($subscription, $newPlan);

        // (40 - 10) / 30 days * 15 days remaining = 15.00
        $this->assertEquals(15.00, (float) $invoice->total_amount);
        $this->assertEquals(40.00, (float) $subscription->fresh()->price);

        Carbon::setTestNow();
    }

    public function test_process_automatic_payment_uses_injected_gateway(): void
    {
        $customer = Customer::factory()->create();
        $gateway = PaymentGateway::create([
            'name' => 'Test Gateway',
            'api_key' => 'key',
            'secret_key' => 'secret',
        ]);
        PaymentMethod::create([
            'customer_id' => $customer->id,
            'payment_gateway_id' => $gateway->id,
            'type' => 'card',
            'is_default' => true,
        ]);
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending',
        ]);

        $result = $this->billingService->processAutomaticPayment($invoice);

        $this->assertTrue($result['success']);
        $this->assertEquals('paid', $invoice->fresh()->status);
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'status' => 'completed',
            'transaction_id' => 'test-txn-123',
        ]);
    }

    public function test_send_overdue_reminders(): void
    {
        Mail::fake();

        Invoice::factory()->create([
            'due_date' => Carbon::yesterday(),
            'status' => 'pending',
        ]);

        $this->billingService->sendOverdueReminders();

        // Verify the method ran without errors
        $this->assertTrue(true);
    }
}
