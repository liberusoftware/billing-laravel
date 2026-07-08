<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderFormTemplate;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * N4 — order-form templates pipeline. A template offers a set of plans; placing
 * an order drives create-subscription -> invoice -> order, scoped to offered plans.
 */
class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private function plan(float $price = 19.99): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Starter',
            'code' => 'starter-'.fake()->unique()->numberBetween(1, 99999),
            'price' => $price,
            'currency' => 'USD',
            'is_active' => true,
            'trial_days' => 0,
        ]);
    }

    public function test_place_order_creates_subscription_invoice_and_order_all_linked(): void
    {
        $customer = Customer::factory()->create();
        $plan = $this->plan(19.99);
        $template = OrderFormTemplate::factory()->offering([$plan->id])->create();

        $order = app(OrderService::class)->placeOrder($template, $customer, [
            'subscription_plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
        ]);

        $this->assertSame('completed', $order->status);
        $this->assertSame($template->id, $order->order_form_template_id);
        $this->assertSame($customer->id, $order->customer_id);
        $this->assertNotNull($order->subscription_id);
        $this->assertNotNull($order->invoice_id);

        $subscription = Subscription::find($order->subscription_id);
        $this->assertSame($plan->id, $subscription->subscription_plan_id);

        $invoice = Invoice::find($order->invoice_id);
        $this->assertEquals(19.99, (float) $invoice->total_amount);
        $this->assertSame('pending', $invoice->status);
    }

    public function test_place_order_rejects_plan_not_offered_by_template(): void
    {
        $customer = Customer::factory()->create();
        $offered = $this->plan();
        $other = $this->plan();
        $template = OrderFormTemplate::factory()->offering([$offered->id])->create();

        try {
            app(OrderService::class)->placeOrder($template, $customer, [
                'subscription_plan_id' => $other->id,
            ]);
            $this->fail('Expected InvalidArgumentException for an unoffered plan.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('not offered', $e->getMessage());
        }

        // Nothing persisted on rejection.
        $this->assertSame(0, Order::count());
        $this->assertSame(0, Subscription::count());
    }

    public function test_place_order_rejects_inactive_plan_even_when_offered(): void
    {
        $customer = Customer::factory()->create();
        $plan = $this->plan();
        $plan->update(['is_active' => false]);
        $template = OrderFormTemplate::factory()->offering([$plan->id])->create();

        try {
            app(OrderService::class)->placeOrder($template, $customer, [
                'subscription_plan_id' => $plan->id,
                'billing_cycle' => 'monthly',
            ]);
            $this->fail('Expected InvalidArgumentException for an inactive plan.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('not available', $e->getMessage());
        }

        $this->assertSame(0, Order::count());
        $this->assertSame(0, Subscription::count());
    }

    public function test_template_offered_plans_round_trip(): void
    {
        $template = OrderFormTemplate::factory()->offering([3, 7])->create();

        $this->assertSame([3, 7], $template->fresh()->offeredPlanIds());
        $this->assertTrue($template->offersPlan(7));
        $this->assertFalse($template->offersPlan(99));
    }
}
