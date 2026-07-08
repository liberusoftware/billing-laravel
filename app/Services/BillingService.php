<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Discount;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\RecurringBillingConfiguration;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\UsageRecord;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class BillingService
{
    protected PaymentPlanService $paymentPlanService;

    protected PaymentGatewayService $paymentGatewayService;

    protected PricingService $pricingService;

    protected SmsService $smsService;

    protected PartialPaymentService $partialPaymentService;

    protected RefundService $refundService;

    public function __construct(
        protected ServiceProvisioningService $serviceProvisioningService,
        protected CurrencyService $currencyService,
        ?PaymentPlanService $paymentPlanService = null,
        ?PaymentGatewayService $paymentGatewayService = null,
        ?PricingService $pricingService = null,
        ?SmsService $smsService = null,
        ?PartialPaymentService $partialPaymentService = null,
        ?RefundService $refundService = null
    ) {
        $this->paymentPlanService = $paymentPlanService ?? new PaymentPlanService($this);
        $this->paymentGatewayService = $paymentGatewayService ?? new PaymentGatewayService;
        $this->pricingService = $pricingService ?? new PricingService;
        $this->smsService = $smsService ?? new SmsService;
        $this->partialPaymentService = $partialPaymentService ?? new PartialPaymentService($this->paymentGatewayService);
        $this->refundService = $refundService ?? new RefundService($this->paymentGatewayService);
    }

    public function createSubscription(Customer $customer, SubscriptionPlan $plan, string $billingCycle): Subscription
    {
        // Price the whole term: a quarterly/annual cycle bills the plan price times
        // the number of months it covers, not a single month's price.
        $cycleTotal = (string) round((float) $plan->price * $this->cycleMonths($billingCycle), 2);

        $subscription = new Subscription(
            [
                'customer_id' => $customer->id,
                'subscription_plan_id' => $plan->id,
                'start_date' => now(),
                'end_date' => $this->calculateEndDate($billingCycle),
                'renewal_period' => $billingCycle,
                'status' => 'pending',
                'price' => $cycleTotal,
                'currency' => $plan->currency,
                'auto_renew' => true,
            ]
        );

        $subscription->save();

        // Flat-rate plan: bill the plan price up front. generateInvoice() is the
        // usage-metered path and requires a product_service, which a plan-based
        // subscription does not have.
        Invoice::create([
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'issue_date' => now(),
            'total_amount' => $cycleTotal,
            'currency' => $plan->currency,
            'status' => 'pending',
            'due_date' => now()->addDays(30),
        ]);

        return $subscription;
    }

    public function upgradeSubscription(Subscription $subscription, SubscriptionPlan $newPlan): Invoice
    {
        // Charge the prorated difference between the new and old plan for the
        // days remaining in the current cycle.
        $proratedAmount = $this->calculateProratedAmount(
            (float) $subscription->price,
            (float) $newPlan->price,
            $subscription
        );

        $invoice = Invoice::create([
            'customer_id' => $subscription->customer_id,
            'subscription_id' => $subscription->id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'issue_date' => now(),
            'total_amount' => $proratedAmount,
            'currency' => $subscription->currency ?? 'USD',
            'status' => 'pending',
            'due_date' => now()->addDays(30),
        ]);

        // NOTE: the subscriptions table has no subscription_plan_id column
        // (plan link is via product_service_id); only the price is updated here.
        $subscription->update(['price' => $newPlan->price]);

        return $invoice;
    }

    public function cancelSubscription(Subscription $subscription): bool
    {
        $subscription->update(
            [
                'status' => 'cancelled',
                'auto_renew' => false,
                'cancelled_at' => now(),
            ]
        );

        // Handle any refunds if necessary
        if ($subscription->end_date->isFuture()) {
            $refundAmount = $this->calculateRefundAmount($subscription);
            if ($refundAmount > 0) {
                // $this->processRefund($subscription->lastPayment, $refundAmount);
            }
        }

        return true;
    }

    private function calculateProratedAmount(float $oldPrice, float $newPrice, Subscription $subscription): float
    {
        // Total length of the current billing cycle, in days.
        $totalDays = max(1, (int) round($subscription->start_date->diffInDays($subscription->end_date)));

        // Whole days left before the cycle ends (clamped to [0, totalDays]).
        $daysRemaining = (int) round(now()->diffInDays($subscription->end_date, false));
        $daysRemaining = max(0, min($daysRemaining, $totalDays));

        // A downgrade (new < old) must not produce a negative invoice; floor at 0.
        return max(0.0, round((($newPrice - $oldPrice) / $totalDays) * $daysRemaining, 2));
    }

    /**
     * Months covered by a billing cycle. Single source of truth for both the
     * end-date and the term price; rejects unknown cycles rather than defaulting.
     */
    private function cycleMonths(string $billingCycle): int
    {
        return match ($billingCycle) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi-annually' => 6,
            'annually' => 12,
            default => throw new InvalidArgumentException("Unknown billing cycle: {$billingCycle}"),
        };
    }

    private function calculateEndDate(string $billingCycle): Carbon
    {
        return now()->addMonths($this->cycleMonths($billingCycle));
    }

    private function calculateRefundAmount(Subscription $subscription): float
    {
        $daysRemaining = now()->diffInDays($subscription->end_date);
        $totalDays = $subscription->start_date->diffInDays($subscription->end_date); //

        return ((float) $subscription->price / $totalDays) * $daysRemaining;

    }

    public function recordUsage(Subscription $subscription, string $metric, float $quantity)
    {
        return $subscription->productService->recordUsage(
            $subscription->id,
            $metric,
            $quantity
        );
    }

    public function calculateUsageCharges(Subscription $subscription, $startDate, $endDate)
    {
        return $this->pricingService->calculatePrice(
            $subscription->productService,
            [
                'subscription_id' => $subscription->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        );
    }

    public function generateInvoice(Subscription $subscription)
    {
        $amount = $this->calculateUsageCharges(
            $subscription,
            $subscription->last_billed_at ?? $subscription->start_date,
            now()
        );

        // Mark usage records as processed
        UsageRecord::where(
            'subscription_id',
            $subscription->id
        )
            ->where(
                'processed',
                false
            )
            ->update(['processed' => true]);

        // Create invoice with calculated amount
        $invoice = Invoice::create(
            [
                'customer_id' => $subscription->customer_id,
                'subscription_id' => $subscription->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'issue_date' => now(),
                'total_amount' => $amount,
                'currency' => $subscription->currency ?? 'USD',
                'status' => 'pending',
                'due_date' => now()->addDays(30),
            ]
        );

        return $invoice;
    }

    // public function convertCurrency($amount, $fromCurrency, $toCurrency)
    // {
    //     return $this->currencyService->convert($amount, $fromCurrency, $toCurrency);
    // }

    public function applyDiscount(Invoice $invoice, string $discountCode): array
    {
        $discount = Discount::where('code', $discountCode)
            ->where('is_active', true)
            ->first();

        if (! $discount || ! $discount->isValid()) {
            return [
                'success' => false,
                'message' => 'Invalid or expired discount code',
            ];
        }

        // Never discount more than the invoice is worth.
        $discountAmount = min(
            (float) $invoice->subtotal + (float) ($invoice->tax_amount ?? 0),
            max(0.0, $this->calculateDiscountAmount($invoice, $discount))
        );

        $invoice->update(
            [
                'discount_id' => $discount->id,
                'discount_amount' => (string) $discountAmount,
                // Keep tax in the total (matches Invoice::final_total = subtotal + tax - discount).
                // Floor at 0 so an oversized discount never writes a negative total.
                'total_amount' => (string) max(0.0, (float) $invoice->subtotal + (float) ($invoice->tax_amount ?? 0) - $discountAmount),
            ]
        );

        $discount->increment('used_count');

        return [
            'success' => true,
            'discount_amount' => $discountAmount,
        ];
    }

    private function calculateDiscountAmount(Invoice $invoice, Discount $discount): float
    {
        if ($discount->type === 'percentage') {
            // A percentage over 100 (or negative) is nonsense; clamp to [0, 100].
            $percentage = min(100.0, max(0.0, (float) $discount->value));

            return (float) $invoice->subtotal * ($percentage / 100);
        }

        if ($discount->type === 'fixed') {
            if ($discount->currency !== $invoice->currency) {
                return (float) $this->convertCurrency(
                    $discount->value,
                    $discount->currency,
                    $invoice->currency
                );
            }

            return (float) $discount->value;
        }

        return 0.0;
    }

    // public function generateInvoice(Subscription $subscription)
    // {
    //     $customer = $subscription->customer;
    //     $amount = $subscription->productService->price;
    //     $currency = $subscription->currency ?? 'USD';

    //     // Get default template or first available
    //     $template = InvoiceTemplate::where('team_id', $customer->team_id)
    //         ->where('is_default', true)
    //         ->first() ?? InvoiceTemplate::where('team_id', $customer->team_id)->first();

    //     $invoice = Invoice::create([
    //         'customer_id' => $customer->id,
    //         'invoice_number' => $this->generateInvoiceNumber(),
    //         'issue_date' => Carbon::now(),
    //         'due_date' => Carbon::now()->addDays(30),
    //         'total_amount' => $amount,
    //         'currency' => $currency,
    //         'status' => 'pending',
    //         'invoice_template_id' => $template?->id,
    //     ]);

    //     // Create invoice item
    //     Invoice_Item::create([
    //         'invoice_id' => $invoice->id,
    //         'product_service_id' => $subscription->productService->id,
    //         'quantity' => 1,
    //         'unit_price' => $amount,
    //         'total_price' => $amount,
    //         'currency' => $currency,
    //     ]);

    //     // Calculate and add tax
    //     $taxAmount = $invoice->calculateTax();
    //     $invoice->update([
    //         'tax_amount' => $taxAmount,
    //         'total_amount' => $invoice->final_total
    //     ]);

    //     // Send invoice email
    //     $invoice->sendInvoiceEmail();

    //     return $invoice;
    // }

    public function setupPaymentPlan(Invoice $invoice, $totalInstallments, $frequency = 'monthly')
    {
        if ($invoice->paymentPlan) {
            throw new Exception('Invoice already has a payment plan');
        }

        return $invoice->createPaymentPlan(
            $totalInstallments,
            $frequency
        );
    }

    public function processPaymentPlans(): void
    {
        $this->paymentPlanService->processPaymentPlans();
    }

    public function convertCurrency($amount, $fromCurrency, $toCurrency)
    {
        return $this->currencyService->convert((float) $amount, $fromCurrency, $toCurrency);
    }

    public function processRecurringBilling(): void
    {
        // Process subscription-based billing
        $this->processSubscriptionBilling();

        // Process recurring invoices
        $this->processRecurringInvoices();
    }

    protected function processSubscriptionBilling()
    {
        $dueSubscriptions = Subscription::where(
            'end_date',
            '<=',
            Carbon::now()
        )
            ->where(
                'status',
                'active'
            )
            ->get();

        foreach ($dueSubscriptions as $subscription) {
            try {
                $invoice = $this->generateInvoice($subscription);

                // Process automatic payment
                $paymentResult = $this->processAutomaticPayment($invoice);

                if ($paymentResult['success']) {
                    $invoice->update(['status' => 'paid']);
                    $subscription->renew();
                    try {
                        $this->serviceProvisioningService->manageService(
                            $subscription,
                            'unsuspend'
                        );
                    } catch (Exception $e) {
                        Log::warning("Could not unsuspend service for subscription {$subscription->id}: ".$e->getMessage());
                    }
                } else {
                    try {
                        $this->serviceProvisioningService->manageService(
                            $subscription,
                            'suspend'
                        );
                    } catch (Exception $e) {
                        Log::warning("Could not suspend service for subscription {$subscription->id}: ".$e->getMessage());
                    }
                }
            } catch (Exception $e) {
                Log::error("Failed to process billing for subscription {$subscription->id}: ".$e->getMessage());
            }
        }
    }

    protected function processRecurringInvoices()
    {
        $configurations = RecurringBillingConfiguration::where(
            'is_active',
            true
        )
            ->where(
                'next_billing_date',
                '<=',
                now()
            )
            ->get();

        foreach ($configurations as $config) {
            $originalInvoice = $config->invoice;

            // Create new invoice based on original
            $newInvoice = $this->generateRecurringInvoice($originalInvoice);

            // Update next billing date
            $config->update(
                [
                    'next_billing_date' => $config->calculateNextBillingDate(),
                ]
            );

            // Process automatic payment
            $this->processAutomaticPayment($newInvoice);
        }
    }

    protected function generateRecurringInvoice(Invoice $originalInvoice)
    {
        $newInvoice = $originalInvoice->replicate();
        $newInvoice->invoice_number = $this->generateInvoiceNumber();
        $newInvoice->issue_date = now();
        $newInvoice->due_date = now()->addDays(30);
        $newInvoice->status = 'pending';
        $newInvoice->is_recurring = true;
        $newInvoice->save();

        // Copy invoice items
        foreach ($originalInvoice->items as $item) {
            $newItem = $item->replicate();
            $newItem->invoice_id = $newInvoice->id;
            $newItem->save();
        }

        return $newInvoice;
    }

    public function processAutomaticPayment(Invoice $invoice): array
    {
        $customer = $invoice->customer;

        // Assuming the customer has a default payment method stored
        $paymentMethod = $customer->defaultPaymentMethod;

        if (! $paymentMethod) {
            $this->handleFailedPayment($invoice);

            return [
                'success' => false,
                'message' => 'No default payment method found',
            ];
        }

        $payment = new Payment(
            [
                'invoice_id' => $invoice->id,
                'payment_gateway_id' => $paymentMethod->payment_gateway_id,
                'amount' => $invoice->total_amount,
                'currency' => $invoice->currency,
                'payment_method' => $paymentMethod->type,
            ]
        );

        try {
            $result = $this->paymentGatewayService->processPayment($payment);
            if ($result['success']) {
                $payment->transaction_id = $result['transaction_id'];
                $payment->status = 'completed';
                $payment->payment_date = now();
                $payment->save();

                $invoice->status = 'paid';
                $invoice->paid_at = now();
                $invoice->save();

                // Unsuspend any suspended hosting accounts
                if ($invoice->subscription) {
                    $hostingAccount = $invoice->subscription->hostingAccount;
                    if ($hostingAccount && $hostingAccount->status === 'suspended') {
                        $this->serviceProvisioningService->manageService(
                            $invoice->subscription,
                            'unsuspend'
                        );

                        Log::info(
                            'Hosting account unsuspended after successful payment',
                            [
                                'invoice_id' => $invoice->id,
                                'hosting_account_id' => $hostingAccount->id,
                            ]
                        );
                    }
                }

                return [
                    'success' => true,
                    'payment' => $payment,
                ];
            } else {
                $this->handleFailedPayment($invoice);

                return [
                    'success' => false,
                    'message' => $result['message'],
                ];
            }
        } catch (Exception $e) {
            $this->handleFailedPayment($invoice);

            return [
                'success' => false,
                'message' => 'Payment processing failed: '.$e->getMessage(),
            ];
        }
    }

    protected function handleFailedPayment(Invoice $invoice)
    {
        // If payment fails and grace period is over, suspend hosting
        if ($invoice->due_date->addDays(
            config(
                'billing.grace_period',
                3
            )
        )->isPast() && $invoice->subscription) {
            $hostingAccount = $invoice->subscription->hostingAccount;
            if ($hostingAccount && $hostingAccount->status === 'active') {
                $this->serviceProvisioningService->manageService(
                    $invoice->subscription,
                    'suspend'
                );

                Log::info(
                    'Hosting account suspended due to failed payment',
                    [
                        'invoice_id' => $invoice->id,
                        'hosting_account_id' => $hostingAccount->id,
                    ]
                );
            }
        }

        $invoice->status = 'overdue';
        $invoice->save();
    }

    // public function sendUpcomingInvoiceReminders()
    // {
    //     $reminderCount = 0;
    //     $teams = Team::all();

    //     foreach ($teams as $team) {
    //         $settings = ReminderSetting::where('team_id', $team->id)
    //             ->where('is_active', true)
    //             ->first();

    //         if (!$settings) {
    //             continue;
    //         }

    //         $upcomingInvoices = Invoice::where('team_id', $team->id)
    //             ->where('status', 'pending')
    //             ->where('due_date', '>', Carbon::now())
    //             ->where('due_date', '<=', Carbon::now()->addDays($settings->days_before_reminder))
    //             ->whereNull('upcoming_reminder_sent')
    //             ->get();

    //         foreach ($upcomingInvoices as $invoice) {
    //             $this->sendUpcomingInvoiceEmail($invoice);
    //             $invoice->update(['upcoming_reminder_sent' => true]);
    //             $reminderCount++;
    //         }
    //     }

    //     return $reminderCount;
    // }

    // private function sendUpcomingInvoiceEmail(Invoice $invoice)
    // {
    //     $customer = $invoice->customer;
    //     $template = EmailTemplate::where('type', 'upcoming_invoice')
    //         ->where(function($query) use ($invoice) {
    //             $query->where('team_id', $invoice->team_id)
    //                   ->orWhere('is_default', true);
    //         })
    //         ->first();

    //     $data = [
    //         'customer_name' => $customer->name,
    //         'invoice_number' => $invoice->invoice_number,
    //         'due_date' => $invoice->due_date->format('Y-m-d'),
    //         'amount' => $invoice->total_amount,
    //         'currency' => $invoice->currency,
    //     ];

    //     Mail::to($customer->email)
    //         ->queue(new UpcomingInvoiceReminder($data, $template));
    // }

    // public function sendOverdueReminders()
    // {
    //     $reminderCount = 0;
    //     $teams = Team::all();

    //     foreach ($teams as $team) {
    //         $settings = ReminderSetting::where('team_id', $team->id)
    //             ->where('is_active', true)
    //             ->first();

    //         if (!$settings) {
    //             continue;
    //         }

    //         $overdueInvoices = Invoice::where('team_id', $team->id)
    //             ->where('due_date', '<', Carbon::now())
    //             ->where('status', 'pending')
    //             ->where(function ($query) use ($settings) {
    //                 $query->whereNull('reminder_count')
    //                     ->orWhere('reminder_count', '<', $settings->max_reminders);
    //             })
    //             ->where(function ($query) use ($settings) {
    //                 $query->whereNull('last_reminder_date')
    //                     ->orWhere('last_reminder_date', '<=',
    //                         Carbon::now()->subDays($settings->reminder_frequency));
    //             })
    //             ->get();

    //         foreach ($overdueInvoices as $invoice) {
    //             // Apply late fee
    //             $invoice->applyLateFee();

    //             // Send reminder email
    //             $this->sendOverdueReminderEmail($invoice);
    //             $this->sendOverdueReminderSms($invoice);

    //             $invoice->update([
    //                 'reminder_count' => ($invoice->reminder_count ?? 0) + 1,
    //                 'last_reminder_date' => Carbon::now()
    //             ]);

    //             // Suspend service if applicable
    //             $this->serviceProvisioningService->manageService($invoice->subscription, 'suspend');

    //             $reminderCount++;
    //         }
    //     }
    // }

    public function sendOverdueReminders(): int
    {
        $overdueInvoices = Invoice::where(
            'status',
            'pending'
        )
            ->where(
                'due_date',
                '<',
                Carbon::now()
            )
            ->get();

        $count = 0;

        foreach ($overdueInvoices as $invoice) {
            try {
                $this->sendOverdueReminderEmail($invoice);
                $invoice->update(
                    [
                        'reminder_count' => ($invoice->reminder_count ?? 0) + 1,
                        'last_reminder_date' => Carbon::now(),
                    ]
                );
                $count++;
            } catch (Exception $e) {
                Log::error("Failed to send overdue reminder for invoice {$invoice->id}: ".$e->getMessage());
            }
        }

        return $count;
    }

    public function sendUpcomingDueReminders(): void
    {
        $upcomingInvoices = Invoice::where(
            'status',
            'pending'
        )
            ->where(
                'due_date',
                '>',
                now()
            )
            ->where(
                'due_date',
                '<=',
                now()->addDays(7)
            )
            ->get();

        foreach ($upcomingInvoices as $invoice) {
            $customer = $invoice->customer;

            if ($customer->sms_notifications_enabled && $customer->phone_number) {
                $daysUntilDue = (int) now()->diffInDays($invoice->due_date);
                $message = $this->getInvoiceReminderMessage(
                    $invoice,
                    $daysUntilDue
                );

                $this->smsService->send(
                    $customer->phone_number,
                    $message
                );

                Log::info(
                    'Upcoming due date reminder SMS sent',
                    [
                        'invoice_id' => $invoice->id,
                        'customer_id' => $customer->id,
                        'days_until_due' => $daysUntilDue,
                    ]
                );
            }
        }
    }

    // protected function sendOverdueReminderSms(Invoice $invoice)
    // {
    //     $customer = $invoice->customer;

    //     if ($customer->sms_notifications_enabled && $customer->phone_number) {
    //         $message = "OVERDUE: Invoice #{$invoice->invoice_number} for " .
    //                   "{$invoice->getFormattedAmount()} was due on {$invoice->due_date->format('Y-m-d')}. " .
    //                   "Please make payment ASAP to avoid additional fees.";

    //         $this->smsService->send(
    //             $customer->phone_number,
    //             $message
    //         );
    //     }

    //     return $reminderCount;
    // }

    protected function getInvoiceReminderMessage(Invoice $invoice, int $daysUntilDue): string
    {
        return "Reminder: Invoice #{$invoice->invoice_number} for ".
            "{$invoice->getFormattedAmount()} is due in {$daysUntilDue} days. ".
            'Please ensure timely payment to avoid late fees.';
    }

    public function sendUpcomingInvoiceReminders(): int
    {
        $upcomingInvoices = Invoice::where(
            'status',
            'pending'
        )
            ->where('due_date', '>', now())
            ->where('due_date', '<=', now()->addDays(7))
            ->whereNull('upcoming_reminder_sent')
            ->get();

        $count = 0;

        foreach ($upcomingInvoices as $invoice) {
            try {
                $customer = $invoice->customer;

                if ($customer && $customer->sms_notifications_enabled && $customer->phone_number) {
                    $daysUntilDue = (int) now()->diffInDays($invoice->due_date);
                    $message = $this->getInvoiceReminderMessage($invoice, $daysUntilDue);
                    $this->smsService->send($customer->phone_number, $message);
                }

                $invoice->update(['upcoming_reminder_sent' => true]);
                $count++;
            } catch (Exception $e) {
                Log::error("Failed to send upcoming invoice reminder for invoice {$invoice->id}: ".$e->getMessage());
            }
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<int, array<string, mixed>>
     */
    public function getBillingSummary(array $parameters): array
    {
        $query = Invoice::query();

        if (isset($parameters['start_date'])) {
            $query->where('issue_date', '>=', $parameters['start_date']);
        }

        if (isset($parameters['end_date'])) {
            $query->where('issue_date', '<=', $parameters['end_date']);
        }

        return $query->get()->map(fn (Invoice $inv) => [
            'invoice_number' => $inv->invoice_number,
            'customer_id' => $inv->customer_id,
            'issue_date' => $inv->issue_date->format('Y-m-d'),
            'due_date' => $inv->due_date->format('Y-m-d'),
            'total_amount' => $inv->total_amount,
            'status' => $inv->status,
            'currency' => $inv->currency,
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<int, array<string, mixed>>
     */
    public function getRevenueReport(array $parameters): array
    {
        $query = Invoice::where('status', 'paid');

        if (isset($parameters['start_date'])) {
            $query->where('paid_at', '>=', $parameters['start_date']);
        }

        if (isset($parameters['end_date'])) {
            $query->where('paid_at', '<=', $parameters['end_date']);
        }

        return $query->get()->map(fn (Invoice $inv) => [
            'invoice_number' => $inv->invoice_number,
            'customer_id' => $inv->customer_id,
            'paid_at' => $inv->paid_at?->format('Y-m-d'),
            'total_amount' => $inv->total_amount,
            'currency' => $inv->currency,
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<int, array<string, mixed>>
     */
    public function getCustomerActivityReport(array $parameters): array
    {
        $query = Customer::query();

        if (isset($parameters['customer_id'])) {
            $query->where('id', $parameters['customer_id']);
        }

        return $query->get()->map(fn (Customer $customer) => [
            'customer_id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'invoice_count' => $customer->invoices()->count(),
            'total_billed' => $customer->invoices()->sum('total_amount'),
        ])->all();
    }

    public function processLateFees(): void
    {
        $pendingInvoices = Invoice::where(
            'status',
            'pending'
        )
            ->where(
                'due_date',
                '<',
                Carbon::now()
            )
            ->get();

        foreach ($pendingInvoices as $invoice) {
            try {
                $fee = $invoice->applyLateFee();
                if ($fee > 0) {
                    // Attempt automatic payment for the late fee
                    $this->processAutomaticPayment($invoice);

                    // Send late fee notification
                    // $this->sendLateFeeNotification($invoice, $fee);
                }
            } catch (Exception $e) {
                Log::error(
                    'Failed to process late fee',
                    [
                        'invoice_id' => $invoice->id,
                        'error' => $e->getMessage(),
                    ]
                );
            }
        }
    }

    private function sendOverdueReminderEmail(Invoice $invoice): void
    {
        $customer = $invoice->customer;
        [
            'customer_name' => $customer->name,
            'invoice_number' => $invoice->invoice_number,
            'due_date' => $invoice->due_date->format('Y-m-d'),
            'amount' => $invoice->total_with_late_fee,
            'original_amount' => $invoice->total_amount,
            'late_fee_amount' => $invoice->late_fee_amount,
            'currency' => $invoice->currency,
        ];

        // Mail::to($customer->email)->send(new OverdueInvoiceReminder($data));
    }

    // private function sendLateFeeNotification(Invoice $invoice, $feeAmount)
    // {
    //     $customer = $invoice->customer;
    //     Mail::to($customer->email)->send(new LateFeeNotification([
    //         'customer_name' => $customer->name,
    //         'invoice_number' => $invoice->invoice_number,
    //         'fee_amount' => $feeAmount,
    //         'total_amount' => $invoice->total_with_late_fee,
    //         'currency' => $invoice->currency,
    //     ]));
    // }

    private function generateInvoiceNumber(): string
    {
        return 'INV-'.strtoupper(uniqid());
    }

    public function handlePartialPayment(Invoice $invoice, float $amount, int $paymentGatewayId): array
    {
        return $this->partialPaymentService->processPartialPayment(
            $invoice,
            $amount,
            $paymentGatewayId
        );
    }

    public function handleRefund(Payment $payment, float $amount): array
    {
        return $this->refundService->processRefund(
            $payment,
            $amount
        );
    }
}
