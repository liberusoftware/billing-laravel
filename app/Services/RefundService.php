<?php

namespace App\Services;

use App\Models\Payment;
use Exception;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function __construct(protected PaymentGatewayService $paymentGatewayService) {}

    public function processRefund(Payment $payment, float $amount): array
    {
        if (! $payment->isRefundable()) {
            throw new Exception('This payment is not refundable.');
        }

        if ($amount > $payment->amount) {
            throw new Exception('Refund amount cannot exceed the original payment amount.');
        }

        DB::beginTransaction();

        try {
            $refundResult = $this->paymentGatewayService->refundPayment(
                $payment,
                $amount
            );

            if ($refundResult['success']) {
                $payment->refund_status = $amount == $payment->amount ? 'full' : 'partial';
                $payment->refunded_amount = ($payment->refunded_amount ?? 0) + $amount;
                $payment->save();

                // Update invoice status if necessary
                $this->updateInvoiceStatus($payment->invoice);

                DB::commit();

                return [
                    'success' => true,
                    'message' => 'Refund processed successfully.',
                ];
            } else {
                throw new Exception($refundResult['message']);
            }
        } catch (Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Refund failed: '.$e->getMessage(),
            ];
        }
    }

    private function updateInvoiceStatus($invoice): void
    {
        $totalPaid = $invoice->payments->sum('amount');
        $totalRefunded = $invoice->payments->sum('refunded_amount');
        $netPaid = $totalPaid - $totalRefunded;

        if ($netPaid <= 0) {
            $invoice->status = 'refunded';
        } elseif ($netPaid < $invoice->total_amount) {
            $invoice->status = 'partially_paid';
        }

        $invoice->save();
    }
}
