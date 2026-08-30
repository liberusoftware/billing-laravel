<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Actions;

use Carbon\CarbonInterface;
use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Invoicing\Events\PaymentPlanInstallmentGenerated;
use Liberu\Billing\Invoicing\Models\Invoice;
use Liberu\Billing\Invoicing\Models\PaymentPlan;

final readonly class RunPaymentPlan
{
    public function __construct(
        private DatabaseManager $database,
        private CreateInvoice $createInvoice,
        private AddInvoiceLine $addInvoiceLine,
        private FinalizeInvoice $finalizeInvoice,
    ) {}

    public function execute(PaymentPlan $paymentPlan, ?CarbonInterface $at = null): Invoice
    {
        $at ??= now();

        return $this->database->transaction(function () use ($paymentPlan, $at): Invoice {
            $plan = PaymentPlan::query()->lockForUpdate()->with('invoice')->findOrFail($paymentPlan->getKey());
            if ($plan->status !== 'active' || $plan->generated_installments >= $plan->total_installments) {
                throw new \LogicException('Payment plan is not active.');
            }
            if ($plan->next_due_at->isAfter($at)) {
                throw new \LogicException('Payment plan installment is not due yet.');
            }

            $parent = $plan->invoice;
            $number = trim((string) $parent->number).'-INST'.($plan->generated_installments + 1);
            $amount = (int) $plan->installment_amount_minor;
            if ($plan->generated_installments + 1 === $plan->total_installments) {
                $amount += (int) (is_array($plan->metadata) ? ($plan->metadata['remainder_minor'] ?? 0) : 0);
            }

            $invoice = $this->createInvoice->execute([
                'team_id' => $parent->team_id,
                'customer_id' => $parent->customer_id,
                'number' => $number,
                'currency' => $parent->currency,
                'due_at' => $plan->next_due_at,
                'metadata' => ['payment_plan_id' => $plan->getKey(), 'parent_invoice_id' => $parent->getKey(), 'installment' => $plan->generated_installments + 1],
            ]);
            $this->addInvoiceLine->execute($invoice, 'Payment plan installment', 1, $amount);
            $invoice = $this->finalizeInvoice->execute($invoice->refresh());

            $generated = $plan->generated_installments + 1;
            $plan->update([
                'generated_installments' => $generated,
                'next_due_at' => $this->nextDueAt($plan->next_due_at, $plan->frequency),
                'status' => $generated >= $plan->total_installments ? 'completed' : 'active',
            ]);
            PaymentPlanInstallmentGenerated::dispatch($invoice->refresh(), (int) $plan->getKey());

            return $invoice->refresh();
        });
    }

    private function nextDueAt(CarbonInterface $date, string $frequency): CarbonInterface
    {
        return match ($frequency) {
            'weekly' => $date->copy()->addWeek(),
            'quarterly' => $date->copy()->addMonths(3),
            default => $date->copy()->addMonth(),
        };
    }
}
