<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Exception;
use Illuminate\Support\Facades\DB;

class PartialPaymentService
{
    public function __construct(protected PaymentGatewayService $paymentGatewayService) {}

    public function processPartialPayment(Invoice $invoice, float $amount, int $paymentGatewayId): array
    {
        if ($amount <= 0 || $amount > $invoice->remaining_amount) {
            return [
                'success' => false,
                'message' => 'Invalid partial payment amount.',
            ];
        }

        DB::beginTransaction();

        try {
            $payment = new Payment(
                [
                    'invoice_id' => $invoice->id,
                    'payment_gateway_id' => $paymentGatewayId,
                    'amount' => $amount,
                    'currency' => $invoice->currency,
                    'payment_method' => 'credit card',
                    'payment_date' => now(),
                ]
            );

            $paymentResult = $this->paymentGatewayService->processPayment($payment);

            if ($paymentResult['success']) {
                $payment->transaction_id = $paymentResult['transaction_id'];
                $payment->save();

                $invoice->updateStatus();

                DB::commit();

                return [
                    'success' => true,
                    'message' => 'Partial payment processed successfully.',
                    'payment' => $payment,
                ];
            } else {
                throw new Exception($paymentResult['message']);
            }
        } catch (Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Partial payment failed: '.$e->getMessage(),
            ];
        }
    }
}
