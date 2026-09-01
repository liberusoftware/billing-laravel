<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Actions;

use Carbon\CarbonInterface;
use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Invoicing\Enums\InvoiceStatus;
use Liberu\Billing\Invoicing\Events\PaymentPlanCreated;
use Liberu\Billing\Invoicing\Models\Invoice;
use Liberu\Billing\Invoicing\Models\PaymentPlan;

final readonly class CreatePaymentPlan
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(Invoice $invoice, int $totalInstallments, string $frequency = 'monthly', ?CarbonInterface $startAt = null): PaymentPlan
    {
        $frequency = strtolower(trim($frequency));
        if ($invoice->status !== InvoiceStatus::Finalized || $totalInstallments < 2 || ! in_array($frequency, ['weekly', 'monthly', 'quarterly'], true) || (int) $invoice->total_minor < 1) {
            throw new \InvalidArgumentException('Payment plan details are invalid.');
        }

        $startAt ??= now();
        $installmentAmount = intdiv((int) $invoice->total_minor, $totalInstallments);
        if ($installmentAmount < 1) {
            throw new \InvalidArgumentException('Payment plan installment amount is invalid.');
        }

        return $this->database->transaction(function () use ($invoice, $totalInstallments, $installmentAmount, $frequency, $startAt): PaymentPlan {
            $plan = PaymentPlan::query()->create([
            'team_id' => $invoice->team_id,
            'invoice_id' => $invoice->getKey(),
            'customer_id' => $invoice->customer_id,
            'total_installments' => $totalInstallments,
            'installment_amount_minor' => $installmentAmount,
            'frequency' => $frequency,
            'start_at' => $startAt,
            'next_due_at' => $this->nextDueAt($startAt, $frequency),
            'status' => 'active',
            'metadata' => ['remainder_minor' => (int) $invoice->total_minor % $totalInstallments],
            ]);
            PaymentPlanCreated::dispatch($plan);

            return $plan;
        });
    }

    private function nextDueAt(CarbonInterface $date, string $frequency): CarbonInterface
    {
        return match ($frequency) {
            'weekly' => $date->copy()->addWeek(),
            'quarterly' => $date->copy()->addMonths(3),
            default => $date->copy()->addMonthNoOverflow(),
        };
    }
}
