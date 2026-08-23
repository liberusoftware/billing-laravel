<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PaymentPlan;
use Carbon\Carbon;
use Carbon\Month;
use Carbon\WeekDay;
use DateTimeInterface;

class PaymentPlanService
{
    public function __construct(protected BillingService $billingService) {}

    public function createInstallmentInvoice(PaymentPlan $paymentPlan)
    {
        $parentInvoice = $paymentPlan->invoice;

        $installmentInvoice = Invoice::create(
            [
                'customer_id' => $parentInvoice->customer_id,
                'invoice_number' => $this->generateInstallmentNumber($parentInvoice),
                'issue_date' => now(),
                'due_date' => $paymentPlan->next_due_date,
                'total_amount' => $paymentPlan->installment_amount,
                'currency' => $parentInvoice->currency,
                'status' => 'pending',
                'parent_invoice_id' => $parentInvoice->id,
                'is_installment' => true,
            ]
        );

        $paymentPlan->update(
            [
                'next_due_date' => $this->calculateNextDueDate(
                    $paymentPlan->next_due_date,
                    $paymentPlan->frequency
                ),
            ]
        );

        return $installmentInvoice;
    }

    public function processPaymentPlans(): void
    {
        $activePlans = PaymentPlan::where(
            'status',
            'active'
        )
            ->where(
                'next_due_date',
                '<=',
                now()
            )
            ->get();

        foreach ($activePlans as $plan) {
            $this->createInstallmentInvoice($plan);

            if ($plan->installments->count() >= $plan->total_installments) {
                $plan->update(['status' => 'completed']);
            }
        }
    }

    private function generateInstallmentNumber(Invoice $parentInvoice): string
    {
        $count = $parentInvoice->installments()->count() + 1;

        return $parentInvoice->invoice_number."-INST{$count}";
    }

    private function calculateNextDueDate(DateTimeInterface|WeekDay|Month|string|int|float|null $date, $frequency)
    {
        return match ($frequency) {
            'weekly' => Carbon::parse($date)->addWeek(),
            'monthly' => Carbon::parse($date)->addMonth(),
            'quarterly' => Carbon::parse($date)->addMonths(3),
            default => Carbon::parse($date)->addMonth(),
        };
    }
}
