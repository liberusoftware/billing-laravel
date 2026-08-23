<?php

declare(strict_types=1);

namespace Tests\Feature\Client;

use App\Filament\Client\Pages\OrderForm;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderFormTemplate;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * N4 — customer-facing storefront order page. Renders an active template's
 * offered plans and places an order for the logged-in customer.
 */
class StorefrontOrderTest extends TestCase
{
    use RefreshDatabase;

    private function plan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Starter',
            'code' => 'starter-'.fake()->unique()->numberBetween(1, 99999),
            'price' => 19.99,
            'currency' => 'USD',
            'is_active' => true,
            'trial_days' => 0,
        ]);
    }

    private function actingInClientPanel(User $user): void
    {
        $this->actingAs($user);
        $panel = Filament::getPanel('client');
        Filament::setCurrentPanel($panel);
        $panel->boot();
    }

    private function customerFor(User $user): Customer
    {
        $customer = Customer::factory()->create();
        $customer->user_id = $user->id;
        $customer->save();

        return $customer;
    }

    public function test_placing_an_offered_plan_creates_order_subscription_and_invoice_for_the_customer(): void
    {
        $user = User::factory()->create();
        $customer = $this->customerFor($user);
        $plan = $this->plan();
        $template = OrderFormTemplate::factory()->offering([$plan->id])->create();

        $this->actingInClientPanel($user);

        Livewire::test(OrderForm::class, ['templateSlug' => $template->slug])
            ->set('selectedPlan', $plan->id)
            ->set('billingCycle', 'monthly')
            ->call('placeOrder');

        $order = Order::where('customer_id', $customer->id)->first();
        $this->assertNotNull($order);
        $this->assertSame('completed', $order->status);
        $this->assertSame($template->id, $order->order_form_template_id);
        $this->assertNotNull($order->subscription_id);
        $this->assertNotNull($order->invoice_id);

        $this->assertSame(1, Subscription::where('customer_id', $customer->id)->count());
    }

    public function test_plan_not_offered_surfaces_error_and_creates_no_order(): void
    {
        $user = User::factory()->create();
        $customer = $this->customerFor($user);
        $offered = $this->plan();
        $other = $this->plan();
        $template = OrderFormTemplate::factory()->offering([$offered->id])->create();

        $this->actingInClientPanel($user);

        Livewire::test(OrderForm::class, ['templateSlug' => $template->slug])
            ->set('selectedPlan', $other->id)
            ->call('placeOrder')
            ->assertNotified();

        $this->assertSame(0, Order::count());
        $this->assertSame(0, Subscription::count());
    }
}
