<?php

namespace App\Services;

use App\Enums\BillingCycle;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderFormTemplate;
use App\Models\Products_Service;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class OrderService
{
    public function __construct(
        protected BillingService $billingService,
    ) {}

    /**
     * Drive a customer order from an order-form template: validate the chosen
     * plan is offered, create the subscription + invoice, record the order, and
     * leave product-backed orders awaiting payment before provisioning.
     *
     * @param  array<string, mixed>  $submittedData  expects subscription_plan_id (+ optional billing_cycle)
     */
    public function placeOrder(OrderFormTemplate $template, Customer $customer, array $submittedData): Order
    {
        $planId = (int) ($submittedData['subscription_plan_id'] ?? 0);

        $billingCycle = (string) ($submittedData['billing_cycle'] ?? 'monthly');
        $customPeriodDays = isset($submittedData['custom_period_days'])
            ? (int) $submittedData['custom_period_days']
            : null;

        $domain = strtolower(rtrim(trim((string) ($submittedData['domain'] ?? '')), '.'));
        $domainAction = (string) ($submittedData['domain_action'] ?? 'existing');

        $checkoutKey = $submittedData['checkout_key'] ?? null;
        if ($checkoutKey !== null && (! is_string($checkoutKey) || ! Str::isUuid($checkoutKey))) {
            throw new InvalidArgumentException('A valid checkout key is required.');
        }
        $checkoutKey = $checkoutKey === null ? null : strtolower($checkoutKey);
        $fingerprint = hash('sha256', json_encode([
            'template_id' => $template->id,
            'plan_id' => $planId,
            'billing_cycle' => $billingCycle,
            'custom_period_days' => $customPeriodDays,
            'domain' => $domain,
            'domain_action' => $domainAction,
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($submittedData, $template, $customer, $planId, $billingCycle, $customPeriodDays, $checkoutKey, $fingerprint, $domain, $domainAction): Order {
            // Serialize submissions for this customer, including when no order exists yet.
            Customer::query()->lockForUpdate()->findOrFail($customer->id);
            if ($checkoutKey !== null) {
                $existing = Order::query()->where('customer_id', $customer->id)
                    ->where('checkout_key', $checkoutKey)->first();
                if ($existing !== null) {
                    if (! hash_equals((string) $existing->checkout_fingerprint, $fingerprint)) {
                        throw new InvalidArgumentException('This checkout has already been submitted with different selections.');
                    }

                    return $existing;
                }
            }

            $template = OrderFormTemplate::query()->lockForUpdate()->findOrFail($template->id);
            if (! $template->is_active) {
                throw new InvalidArgumentException('This order form is no longer available.');
            }
            if ($planId === 0 || ! $template->offersPlan($planId)) {
                throw new InvalidArgumentException('The selected plan is not offered by this order form.');
            }
            $plan = SubscriptionPlan::query()->lockForUpdate()->findOrFail($planId);
            if (! $plan->is_active) {
                throw new InvalidArgumentException('The selected plan is not available for ordering.');
            }

            if (array_key_exists('expected_quote', $submittedData)) {
                $cycle = BillingCycle::tryFrom($billingCycle)
                    ?? throw new InvalidArgumentException('Select a valid billing cycle.');
                $currentQuote = [
                    'total' => number_format(round((float) $plan->price * $cycle->priceMultiplier(), 2), 2, '.', ''),
                    'currency' => $plan->currency,
                ];
                if ($submittedData['expected_quote'] !== $currentQuote) {
                    throw new InvalidArgumentException('The price has changed. Review the updated total and submit again.');
                }
            }

            $productId = $template->hostingProductIdForPlan($planId);
            if ($productId !== null) {
                if (! array_key_exists($billingCycle, BillingCycle::hostingOptions())) {
                    throw new InvalidArgumentException('Select a supported hosting billing cycle.');
                }
                $product = Products_Service::query()->find($productId);
                if ($product === null || $product->type !== 'hosting') {
                    throw new InvalidArgumentException('The hosting package is not available for ordering.');
                }
                if ($domainAction !== 'existing') {
                    throw new InvalidArgumentException('This checkout currently requires an existing domain.');
                }
                if (strlen($domain) > 253 || ! preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/D', $domain)) {
                    throw new InvalidArgumentException('Enter a valid domain name without a URL or path.');
                }
            }

            $subscription = $this->billingService->createSubscription(
                $customer,
                $plan,
                $billingCycle,
                $customPeriodDays
            );
            if ($productId !== null) {
                $subscription->update(['product_service_id' => $productId, 'domain' => $domain]);
            }
            $invoice = Invoice::where('subscription_id', $subscription->id)->latest('id')->first();

            $order = Order::create([
                'order_form_template_id' => $template->id,
                'customer_id' => $customer->id,
                'subscription_id' => $subscription->id,
                'invoice_id' => $invoice?->id,
                'status' => $productId !== null ? 'pending' : 'completed',
                'submitted_data' => [
                    'subscription_plan_id' => $planId,
                    'billing_cycle' => $billingCycle,
                    'custom_period_days' => $customPeriodDays,
                    'domain' => $domain,
                    'domain_action' => $domainAction,
                ],
                'checkout_key' => $checkoutKey,
                'checkout_fingerprint' => $fingerprint,
            ]);

            return $order;
        });
    }
}
