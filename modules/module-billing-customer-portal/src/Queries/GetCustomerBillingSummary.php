<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Queries;

use Carbon\CarbonInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Schema;

final readonly class GetCustomerBillingSummary
{
    public function __construct(private DatabaseManager $database) {}

    /** @return array{history:list<array<string,mixed>>,status:array<string,int>,trends:list<array{month:string,total_paid:int}>} */
    public function handle(int $teamId, int $customerId, ?CarbonInterface $startAt = null, ?CarbonInterface $endAt = null): array
    {
        if ($teamId < 1 || $customerId < 1) {
            throw new \InvalidArgumentException('A valid team and customer are required.');
        }
        if (Schema::hasTable('customers')) {
            $customer = $this->database->table('customers')->where('id', $customerId)->first(['team_id']);
            if ($customer === null || ($customer->team_id !== null && (int) $customer->team_id !== $teamId)) {
                throw new \InvalidArgumentException('Customer reference is invalid.');
            }
        }
        if (! Schema::hasTable('billing_invoices')) {
            return ['history' => [], 'status' => ['total_invoiced' => 0, 'total_paid' => 0, 'total_outstanding' => 0, 'overdue_amount' => 0], 'trends' => []];
        }

        $invoices = $this->database->table('billing_invoices')->where('team_id', $teamId)->where('customer_id', $customerId)
            ->when($startAt !== null, fn ($query) => $query->where('created_at', '>=', $startAt))
            ->when($endAt !== null, fn ($query) => $query->where('created_at', '<=', $endAt))->orderByDesc('created_at')->get();
        $paidByInvoice = $this->paidByInvoice($teamId, $customerId);
        $history = $invoices->map(function (object $invoice) use ($paidByInvoice): array {
            $paid = (int) ($paidByInvoice[(int) $invoice->id] ?? 0);

            return ['invoice_number' => $invoice->number, 'date' => (string) $invoice->created_at, 'due_at' => $invoice->due_at, 'amount_minor' => (int) $invoice->total_minor, 'status' => (string) $invoice->status, 'paid_minor' => $paid, 'balance_minor' => max(0, (int) $invoice->total_minor - $paid), 'currency' => $invoice->currency];
        })->values()->all();
        $totalInvoiced = $invoices->sum(fn (object $invoice): int => (int) $invoice->total_minor);
        $totalPaid = array_sum($paidByInvoice);
        $outstanding = $invoices->filter(fn (object $invoice): bool => in_array((string) $invoice->status, ['finalized', 'pending'], true))->sum(fn (object $invoice): int => (int) $invoice->total_minor);
        $overdue = $invoices->filter(fn (object $invoice): bool => in_array((string) $invoice->status, ['finalized', 'pending'], true) && $invoice->due_at !== null && (string) $invoice->due_at < now()->toDateTimeString())->sum(fn (object $invoice): int => (int) $invoice->total_minor);

        return ['history' => $history, 'status' => ['total_invoiced' => (int) $totalInvoiced, 'total_paid' => (int) $totalPaid, 'total_outstanding' => (int) $outstanding, 'overdue_amount' => (int) $overdue], 'trends' => $this->paymentTrends($teamId, $customerId)];
    }

    /** @return array<int,int> */
    private function paidByInvoice(int $teamId, int $customerId): array
    {
        if (! Schema::hasTable('billing_payment_allocations') || ! Schema::hasTable('billing_payments')) {
            return [];
        }

        return $this->database->table('billing_payment_allocations as allocations')->join('billing_payments as payments', 'payments.id', '=', 'allocations.payment_id')->where('payments.team_id', $teamId)->where('payments.customer_id', $customerId)->whereIn('payments.status', ['captured', 'succeeded', 'paid'])->whereNotNull('allocations.invoice_id')->groupBy('allocations.invoice_id')->selectRaw('allocations.invoice_id, SUM(allocations.amount_minor) as paid_total')->pluck('paid_total', 'invoice_id')->map(fn (mixed $amount): int => (int) $amount)->all();
    }

    /** @return list<array{month:string,total_paid:int}> */
    private function paymentTrends(int $teamId, int $customerId): array
    {
        if (! Schema::hasTable('billing_payments')) {
            return [];
        }

        return $this->database->table('billing_payments')->where('team_id', $teamId)->where('customer_id', $customerId)->whereIn('status', ['captured', 'succeeded', 'paid'])->orderByDesc('created_at')->get(['created_at', 'amount_minor'])->groupBy(fn (object $payment): string => substr((string) $payment->created_at, 0, 7))->take(12)->map(fn ($payments, string $month): array => ['month' => $month, 'total_paid' => (int) $payments->sum('amount_minor')])->values()->all();
    }
}
