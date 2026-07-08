<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\SubscriptionPlan;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * R1.a — createSubscription for a flat-rate plan. The subscriptions table is
 * wired to product_service_id (usage model); a plan-based subscription has no
 * product service, so the FK must be nullable and the plan link real. The
 * initial invoice bills the flat plan price, not usage.
 */
class CreateSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function plan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Pro',
            'code' => 'pro',
            'price' => 29.99,
            'currency' => 'USD',
            'is_active' => true,
            'trial_days' => 0,
        ]);
    }

    public function test_creates_plan_based_subscription_without_product_service(): void
    {
        $customer = Customer::factory()->create();
        $plan = $this->plan();

        $subscription = app(BillingService::class)->createSubscription($customer, $plan, 'monthly');

        $this->assertTrue($subscription->exists);
        $this->assertSame($customer->id, $subscription->customer_id);
        $this->assertSame($plan->id, $subscription->subscription_plan_id);
        $this->assertNull($subscription->product_service_id);
        $this->assertSame('pending', $subscription->status);
        $this->assertEquals(29.99, (float) $subscription->price);
        $this->assertEquals(
            now()->addMonth()->toDateString(),
            $subscription->end_date->toDateString()
        );
    }

    public function test_generates_initial_invoice_at_flat_plan_price(): void
    {
        $customer = Customer::factory()->create();
        $plan = $this->plan();

        $subscription = app(BillingService::class)->createSubscription($customer, $plan, 'monthly');

        $invoice = Invoice::where('subscription_id', $subscription->id)->first();

        $this->assertNotNull($invoice);
        $this->assertEquals(29.99, (float) $invoice->total_amount);
        $this->assertSame('USD', $invoice->currency);
        $this->assertSame('pending', $invoice->status);
    }

    public function test_annual_cycle_bills_twelve_times_the_plan_price(): void
    {
        $customer = Customer::factory()->create();
        $plan = $this->plan();

        $subscription = app(BillingService::class)->createSubscription($customer, $plan, 'annually');

        $this->assertEquals(29.99 * 12, (float) $subscription->price);
        $this->assertEquals(
            now()->addYear()->toDateString(),
            $subscription->end_date->toDateString()
        );

        $invoice = Invoice::where('subscription_id', $subscription->id)->first();
        $this->assertEquals(29.99 * 12, (float) $invoice->total_amount);
    }

    public function test_unknown_billing_cycle_is_rejected(): void
    {
        $customer = Customer::factory()->create();
        $plan = $this->plan();

        $this->expectException(InvalidArgumentException::class);

        app(BillingService::class)->createSubscription($customer, $plan, 'weekly');
    }

    public function test_plan_subscriptions_relationship_resolves(): void
    {
        $customer = Customer::factory()->create();
        $plan = $this->plan();

        app(BillingService::class)->createSubscription($customer, $plan, 'monthly');

        $this->assertCount(1, $plan->refresh()->subscriptions);
    }
}
