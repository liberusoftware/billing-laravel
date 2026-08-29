<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Actions;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Schema;

final readonly class GenerateCustomerBillingSummary
{
    public function __construct(private DatabaseManager $database) {}

    /** @return array{customer_id:int,total_invoiced:int,total_paid:int,total_outstanding:int,overdue_amount:int,currency:?string} */
    public function execute(int $teamId, int $customerId, ?string $currency = null): array
    {
        if ($teamId < 1 || $customerId < 1) {
            throw new \InvalidArgumentException('A valid team and customer are required.');
        }

        $currency = $currency === null ? null : strtoupper($currency);
        if ($currency !== null && ! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \InvalidArgumentException('Billing summary currency is invalid.');
        }

        $invoiced = 0;
        $outstanding = 0;
        $overdue = 0;
        if (Schema::hasTable('billing_invoices')) {
            $query = $this->database->table('billing_invoices')->where('team_id', $teamId)->where('customer_id', $customerId)->when($currency !== null, fn ($q) => $q->where('currency', $currency));
            $invoiced = (int) $query->sum('total_minor');
            $outstandingQuery = (clone $query)->whereIn('status', ['draft', 'open', 'finalized', 'pending', 'overdue']);
            $outstanding = (int) $outstandingQuery->sum('total_minor');
            $overdue = (int) (clone $outstandingQuery)->whereNotNull('due_at')->where('due_at', '<', now())->sum('total_minor');
        }

        $paid = 0;
        if (Schema::hasTable('billing_payments')) {
            $paid = (int) $this->database->table('billing_payments')->where('team_id', $teamId)->where('customer_id', $customerId)->whereIn('status', ['captured', 'succeeded', 'paid'])->when($currency !== null, fn ($q) => $q->where('currency', $currency))->selectRaw('COALESCE(SUM(amount_minor - refunded_minor), 0) as total')->value('total');
        }

        return ['customer_id' => $customerId, 'total_invoiced' => $invoiced, 'total_paid' => $paid, 'total_outstanding' => $outstanding, 'overdue_amount' => $overdue, 'currency' => $currency];
    }
}
