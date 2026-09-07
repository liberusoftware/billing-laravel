<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentHistory;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PaymentReconciliationService
{
    public function reconcilePayment(Payment $payment): bool
    {
        try {
            // Try to match payment with invoice
            $invoice = $this->findMatchingInvoice($payment);

            if ($invoice) {
                return $this->processReconciliation(
                    $payment,
                    $invoice
                );
            }

            // Mark as unreconciled if no match found
            $payment->update(
                [
                    'reconciliation_status' => 'unmatched',
                    'reconciliation_notes' => 'No matching invoice found',
                ]
            );

            $this->logReconciliationHistory(
                $payment,
                null,
                'unmatched'
            );

            return false;
        } catch (Exception $e) {
            Log::error(
                'Payment reconciliation failed',
                [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]
            );

            $payment->update(
                [
                    'reconciliation_status' => 'failed',
                    'reconciliation_notes' => $e->getMessage(),
                ]
            );

            return false;
        }
    }

    protected function findMatchingInvoice(Payment $payment)
    {
        // Try to match by invoice_id if available
        if ($payment->invoice_id) {
            return Invoice::find($payment->invoice_id);
        }

        // note: same customer + same total can match multiple pending invoices;
        // we pick the oldest by due_date. Tighten to a unique match if collisions matter.
        return Invoice::where(
            'customer_id',
            $payment->customer_id
        )
            ->where(
                'total_amount',
                $payment->amount
            )
            ->where(
                'status',
                'pending'
            )
            ->orderBy('due_date')
            ->first();
    }

    protected function processReconciliation(Payment $payment, Invoice $invoice): bool
    {
        return DB::transaction(function () use ($payment, $invoice): bool {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $discrepancy = $this->checkForDiscrepancies($payment, $invoice);
            if ($discrepancy !== null) {
                $payment->update([
                    'reconciliation_status' => 'discrepancy',
                    'reconciliation_notes' => $discrepancy,
                ]);
                $this->logReconciliationHistory($payment, $invoice, 'discrepancy');

                return false;
            }

            $payment->update([
                'invoice_id' => $invoice->id,
                'reconciliation_status' => 'reconciled',
                'reconciliation_notes' => null,
            ]);
            $invoice->updateStatus();
            if ($invoice->status === 'paid' && $invoice->paid_at === null) {
                $invoice->update(['paid_at' => now()]);
            }
            app(HostingFulfillmentService::class)->prepare($invoice);
            $this->logReconciliationHistory($payment, $invoice, 'reconciled');

            return true;
        });
    }

    protected function checkForDiscrepancies(Payment $payment, Invoice $invoice): ?string
    {
        if ($payment->amount != $invoice->total_amount) {
            return "Payment amount ({$payment->amount}) does not match invoice amount ({$invoice->total_amount})";
        }

        if ($payment->currency != $invoice->currency) {
            return "Payment currency ({$payment->currency}) does not match invoice currency ({$invoice->currency})";
        }

        if ($payment->status !== 'completed' || blank($payment->transaction_id)) {
            return 'Payment has not been confirmed as completed.';
        }

        if ((float) $payment->refunded_amount > 0 || in_array($payment->refund_status, ['full', 'completed'], true)) {
            return 'Payment has been refunded.';
        }

        if ($payment->customer_id !== null && $payment->customer_id !== $invoice->customer_id) {
            return 'Payment and invoice belong to different customers.';
        }

        return null;
    }

    protected function logReconciliationHistory(Payment $payment, ?Invoice $invoice, string $status)
    {
        PaymentHistory::create(
            [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice?->id,
                'customer_id' => $payment->customer_id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'payment_method' => $payment->payment_method,
                'transaction_id' => $payment->transaction_id,
                'status' => $status,
                'notes' => $payment->reconciliation_notes,
            ]
        );
    }

    public function handleManualReconciliation(Payment $payment, Invoice $invoice): bool
    {
        // A payment may only be reconciled against an invoice for the same customer.
        if ($payment->customer_id !== $invoice->customer_id) {
            throw new InvalidArgumentException('Payment and invoice belong to different customers.');
        }

        return $this->processReconciliation(
            $payment,
            $invoice
        );
    }
}
