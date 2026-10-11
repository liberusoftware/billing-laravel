# Billing Architecture

## Overview

The billing platform manages the full revenue lifecycle: product catalog, subscription lifecycle, invoicing, payment processing, revenue recognition, and tax compliance. It supports multi-currency, multi-gateway operations with configurable dunning and proration.

## Product and Pricing Catalog

### Plan Structure

| Field | Description |
|-------|-------------|
| `name` | Human-readable plan name |
| `slug` | URL-safe identifier |
| `billing_cycle` | `monthly`, `quarterly`, `annual`, `custom` |
| `base_price` | Recurring charge amount |
| `currency` | ISO 4217 currency code |
| `trial_days` | Optional trial period |
| `features` | JSON map of feature flags and limits |

### Pricing Tiers

Plans support tiered pricing with volume-based or per-unit rates:

```php
Plan::make('pro')
    ->tier('0-100', price: 0.10)
    ->tier('101-1000', price: 0.08)
    ->tier('1001+', price: 0.05);
```

### Metered Billing

Metered usage is tracked per billing period with configurable aggregation (sum, max, last) and rated against tiered price cards.

### Add-ons

Add-ons are independently purchasable items that attach to a subscription. They support one-time, recurring, and usage-based pricing models.

## Subscription Lifecycle

### State Machine

```
lead → trialing → active → past_due → canceled
                  ↘ paused → active     ↘ expired
```

### Key Transitions

| Transition | Trigger | Side Effects |
|------------|---------|--------------|
| `trialing → active` | Trial ends | Generate first invoice, activate features |
| `active → past_due` | Payment failed | Start dunning sequence, notify customer |
| `past_due → active` | Payment succeeds | Reactivate features, clear dunning |
| `active → paused` | User request | Suspend features, stop billing |
| `active → canceled` | User request or dunning exhaustion | Final invoice, data retention window |

### Proration

Mid-cycle plan changes are prorated using daily-rate calculation with configurable rounding (up, down, nearest).

### Dunning

Dunning is a configurable sequence of retry attempts and notifications:

| Attempt | Delay | Action |
|---------|-------|--------|
| 1 | Day 1 | Email notification + retry |
| 2 | Day 3 | Email + SMS + retry |
| 3 | Day 5 | Email + retry |
| 4 | Day 7 | Final notice + retry |
| 5 | Day 10 | Cancel subscription |

## Invoice Generation and Delivery

### Invoice Structure

Invoices contain line items, tax breakdowns, discounts, and payment status. Each invoice has a unique sequential number per tenant.

### Templates

Invoice templates are Blade-based with customizable sections:

```blade
@foreach($invoice->lineItems as $item)
    <tr>
        <td>{{ $item->description }}</td>
        <td>{{ $item->quantity }}</td>
        <td>{{ $item->unit_price }}</td>
        <td>{{ $item->total }}</td>
    </tr>
@endforeach
```

### Multi-Currency

Invoices are generated in the customer's currency with exchange rate locking at generation time. Supports 150+ currencies with configurable decimal precision.

### Delivery

Invoices are delivered via email, customer portal, and webhook. PDF generation is queued asynchronously.

## Payment Processing

### Gateway Abstraction

```php
interface PaymentGatewayInterface
{
    public function charge(PaymentRequest $request): PaymentResponse;
    public function refund(string $transactionId, float $amount): RefundResponse;
    public function capture(string $authorizationId): CaptureResponse;
    public function void(string $transactionId): void;
}
```

### Supported Gateways

- Stripe
- PayPal
- Adyen
- Braintree
- Authorize.net

### Payment Mandates

SEPA, ACH, and card-on-file mandates are stored with mandate reference, status, and cancellation tracking.

### Disputes

Dispute webhooks are ingested, evidence is compiled automatically, and cases are tracked through resolution.

## Revenue Recognition

### Scheduling

Revenue is recognized over the service period using straight-line or custom schedules:

```php
RevenueSchedule::for($subscription)
    ->method('straight_line')
    ->start($subscription->current_period_start)
    ->end($subscription->current_period_end);
```

### Allocation

Multi-element arrangements allocate transaction price to performance obligations using relative standalone selling price.

### Reconciliation

Daily reconciliation jobs compare recognized revenue against gateway settlements and flag discrepancies.

## Tax Engine

### Tax Profiles

Tax profiles define the rules for a jurisdiction:

| Field | Description |
|-------|-------------|
| `jurisdiction` | Country, state, or region code |
| `rate` | Tax rate percentage |
| `type` | `vat`, `sales_tax`, `gst` |
| `threshold` | Revenue threshold for registration |
| `exemptions` | Product categories exempt from tax |

### Jurisdiction Resolution

Tax jurisdiction is resolved from customer billing address, IP geolocation, or tax registration number, in priority order.

### Exemptions

Tax-exempt customers (non-profits, resellers) upload exemption certificates that are validated and stored with expiration tracking.

## Customer Portal

### Billing Management

The customer portal provides:

- Subscription management (upgrade, downgrade, cancel)
- Payment method management (add, remove, set default)
- Invoice history and download
- Usage dashboard with real-time metered consumption

### Self-Service Actions

| Action | Confirmation Required |
|--------|----------------------|
| Upgrade plan | No |
| Downgrade plan | Yes (end of cycle) |
| Cancel subscription | Yes (retention offer) |
| Update payment method | No |
| Download invoice | No |

## Module Boundaries and Extension Points

| Boundary | Extension Point | Mechanism |
|----------|----------------|-----------|
| Catalog | Custom plan types, pricing models | `PlanTypeInterface` binding |
| Subscription | Custom lifecycle hooks | Event listeners on subscription transitions |
| Invoicing | Custom templates, delivery channels | Blade override + `InvoiceDeliveryInterface` |
| Payments | Custom gateways | `PaymentGatewayInterface` binding |
| Revenue | Custom recognition methods | `RevenueMethodInterface` binding |
| Tax | Custom tax calculators | `TaxCalculatorInterface` binding |
| Dunning | Custom retry strategies | `DunningStrategyInterface` binding |
| Portal | Custom self-service actions | Route + view registration |
