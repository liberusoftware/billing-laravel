<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentReconciliationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaddleSettlementController extends Controller
{
    public function __invoke(Request $request, PaymentReconciliationService $reconciliation): JsonResponse
    {
        abort_unless($this->validSignature($request), 401);
        if ($request->input('event_type') !== 'transaction.completed') {
            return response()->json(['received' => true]);
        }
        $data = $request->validate([
            'data.id' => ['required', 'string', 'regex:/^txn_[a-z0-9]+$/'],
            'data.status' => ['required', 'in:completed'],
            'data.currency_code' => ['required', 'string', 'size:3'],
            'data.custom_data.billing_payment_id' => ['required', 'integer', 'min:1'],
            'data.details.totals.grand_total' => ['required', 'string', 'regex:/^[0-9]+$/'],
        ])['data'];
        $candidate = Payment::query()->findOrFail($data['custom_data']['billing_payment_id']);
        DB::transaction(function () use ($candidate, $data, $reconciliation): void {
            // Match reconciliation's lock order so concurrent deliveries serialize safely.
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($candidate->invoice_id);
            $payment = Payment::query()->lockForUpdate()->findOrFail($candidate->getKey());
            $gateway = $payment->paymentGateway;
            abort_unless($payment->invoice_id === $invoice->getKey()
                && (string) $payment->payment_gateway_id === (string) config('services.paddle.webhook_gateway_id')
                && $gateway?->name === 'Paddle' && $gateway->is_active
                && $gateway->team_id === $invoice->team_id && $payment->team_id === $invoice->team_id
                && $payment->customer_id === $invoice->customer_id
                && $payment->transaction_id === $data['id']
                && $payment->currency === $data['currency_code'] && $invoice->currency === $data['currency_code'], 422);
            // Paddle's supported currencies use two decimals except JPY, KRW, and CLP.
            // https://developer.paddle.com/concepts/sell/supported-currencies
            $precision = in_array($payment->currency, ['JPY', 'KRW', 'CLP'], true) ? 0 : 2;
            $minor = bcmul(number_format($payment->amount, 2, '.', ''), (string) (10 ** $precision), 3);
            $expected = bcadd($minor, '0', 0);
            abort_unless(bccomp($minor, $expected, 3) === 0 && bccomp($expected, '0', 0) > 0
                && bccomp($expected, $data['details']['totals']['grand_total'], 0) === 0
                && (float) $payment->refunded_amount === 0.0
                && ! in_array($payment->refund_status, ['full', 'completed'], true), 422);
            if ($payment->status === 'completed') {
                return;
            }
            abort_unless($payment->status === 'pending', 422);
            $payment->forceFill(['status' => 'completed'])->save();
            abort_unless($reconciliation->handleManualReconciliation($payment, $invoice), 422);
        }, 3);

        return response()->json(['received' => true]);
    }

    private function validSignature(Request $request): bool
    {
        // https://developer.paddle.com/webhooks/about/signature-verification
        $secret = config('services.paddle.webhook_secret');
        if (! is_string($secret) || $secret === '') {
            return false;
        }
        $timestamp = null;
        $signatures = [];
        foreach (explode(';', (string) $request->header('Paddle-Signature')) as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) !== 2) {
                continue;
            }
            if ($pair[0] === 'ts' && ctype_digit($pair[1])) {
                if ($timestamp !== null) {
                    return false;
                }
                $timestamp = $pair[1];
            } elseif ($pair[0] === 'h1') {
                $signatures[] = $pair[1];
            }
        }
        if ($timestamp === null || abs(now()->timestamp - (int) $timestamp) > 5) {
            return false;
        }
        $expected = hash_hmac('sha256', $timestamp.':'.$request->getContent(), $secret);
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }
}
