<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InvoiceCheckoutService
{
    public function paddleCheckoutUrl(Invoice $invoice, User $user): string
    {
        $payment = DB::transaction(function () use ($invoice, $user): Payment {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());
            abort_unless($invoice->customer?->user_id === $user->getKey(), 403);
            if (! in_array($invoice->status, ['pending', 'overdue'], true)) {
                throw new RuntimeException('This invoice cannot be paid through checkout.');
            }
            $subscription = $invoice->subscription;
            $code = $subscription?->subscriptionPlan?->code;
            $period = $subscription?->renewal_period;
            $price = config('services.paddle.invoice_prices', [])[$code][$period] ?? null;
            if (! is_array($price) || ! isset($price['price_id'], $price['amount'], $price['currency'])
                || number_format((float) $price['amount'], 2, '.', '') !== number_format((float) $invoice->total_amount, 2, '.', '')
                || $price['currency'] !== $invoice->currency) {
                throw new RuntimeException('No matching Paddle catalog price is configured.');
            }
            $gateway = PaymentGateway::query()->whereKey(config('services.paddle.webhook_gateway_id'))
                ->where('is_active', true)->where('name', 'Paddle')->firstOrFail();
            if ($gateway->team_id !== $invoice->team_id) {
                throw new RuntimeException('The payment gateway belongs to another team.');
            }
            $existing = Payment::query()->where('invoice_id', $invoice->getKey())
                ->where('payment_gateway_id', $gateway->getKey())->whereNotIn('status', ['failed', 'cancelled'])->first();
            if ($existing !== null) {
                return $existing;
            }
            $payment = new Payment();
            $payment->forceFill([
                'invoice_id' => $invoice->getKey(), 'customer_id' => $invoice->customer_id,
                'team_id' => $invoice->team_id, 'payment_gateway_id' => $gateway->getKey(),
                'payment_date' => now(), 'amount' => $invoice->total_amount, 'currency' => $invoice->currency,
                'payment_method' => 'paddle', 'status' => 'pending',
                'payment_method_details' => ['paddle_price_id' => $price['price_id']],
            ])->save();

            return $payment;
        });
        $result = app(PaymentGatewayService::class)->processPayment($payment);
        $url = is_array($result) ? ($result['checkout']['url'] ?? null) : null;
        if (! is_string($url) || parse_url($url, PHP_URL_SCHEME) !== 'https') {
            throw new RuntimeException('Paddle did not return a secure checkout URL.');
        }

        return $url;
    }
}
