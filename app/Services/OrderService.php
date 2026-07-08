<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderFormTemplate;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class OrderService
{
    public function __construct(
        protected BillingService $billingService,
        protected ServiceProvisioningService $provisioningService,
    ) {}

    /**
     * Drive a customer order from an order-form template: validate the chosen
     * plan is offered, create the subscription + invoice, record the order, and
     * trigger provisioning when the subscription is product-backed.
     *
     * @param  array<string, mixed>  $submittedData  expects subscription_plan_id (+ optional billing_cycle)
     */
    public function placeOrder(OrderFormTemplate $template, Customer $customer, array $submittedData): Order
    {
        $planId = (int) ($submittedData['subscription_plan_id'] ?? 0);

        if ($planId === 0 || ! $template->offersPlan($planId)) {
            throw new InvalidArgumentException('The selected plan is not offered by this order form.');
        }

        $plan = SubscriptionPlan::findOrFail($planId);

        if (! $plan->is_active) {
            throw new InvalidArgumentException('The selected plan is not available for ordering.');
        }

        $billingCycle = (string) ($submittedData['billing_cycle'] ?? 'monthly');

        return DB::transaction(function () use ($template, $customer, $plan, $billingCycle, $submittedData): Order {
            $subscription = $this->billingService->createSubscription($customer, $plan, $billingCycle);
            $invoice = Invoice::where('subscription_id', $subscription->id)->latest('id')->first();

            $order = Order::create([
                'order_form_template_id' => $template->id,
                'customer_id' => $customer->id,
                'subscription_id' => $subscription->id,
                'invoice_id' => $invoice?->id,
                'status' => 'completed',
                'submitted_data' => $submittedData,
            ]);

            // Provisioning only applies to product-backed subscriptions. Flat-rate
            // plan orders have no product_service to provision.
            // ponytail: product-backed order forms provision here; plan-only orders skip.
            if ($subscription->product_service_id !== null) {
                try {
                    $this->provisioningService->provisionService($subscription);
                } catch (Throwable) {
                    $order->update(['status' => 'failed']);
                }
            }

            return $order;
        });
    }
}
