<?php

declare(strict_types=1);

namespace Liberu\Billing\Paddle;

use Illuminate\Http\Client\Factory;
use Liberu\Billing\Payments\Contracts\GatewayDriver;
use Liberu\Billing\Payments\Models\Payment;

final readonly class PaddleGatewayDriver implements GatewayDriver
{
    public function __construct(private Factory $http) {}

    public function capture(Payment $payment): array
    {
        $details = is_array($payment->metadata) ? $payment->metadata : [];
        $priceId = $details['paddle_price_id'] ?? null;
        if (! is_string($priceId) || ! preg_match('/^pri_[a-z0-9]+$/', $priceId)) {
            throw new \InvalidArgumentException('A Paddle price ID is required.');
        }

        $payload = ['collection_mode' => 'automatic', 'items' => [['price_id' => $priceId, 'quantity' => max(1, (int) ($details['paddle_quantity'] ?? 1))]], 'custom_data' => ['billing_payment_id' => (string) $payment->getKey()]];
        if (isset($details['paddle_customer_id'])) {
            $payload['customer_id'] = (string) $details['paddle_customer_id'];
        }
        $transaction = $this->request('post', 'transactions', $payload);
        $status = data_get($transaction, 'data.status');
        $reference = data_get($transaction, 'data.id');
        // `ready` only means that Paddle has prepared the transaction for
        // checkout; it is not evidence that funds were collected.
        if (! in_array($status, ['completed', 'billed', 'paid'], true) || ! is_string($reference) || $reference === '') {
            throw new \RuntimeException('Paddle transaction is not complete: '.(string) $status);
        }

        return ['reference' => $reference];
    }

    public function refund(Payment $payment, int $amountMinor): array
    {
        $reference = (string) $payment->provider_reference;
        if ($reference === '') {
            throw new \InvalidArgumentException('A Paddle transaction reference is required for refunds.');
        }
        $details = is_array($payment->metadata) ? $payment->metadata : [];
        $full = $amountMinor >= (int) $payment->amount_minor;
        $payload = ['action' => 'refund', 'type' => $full ? 'full' : 'partial', 'transaction_id' => $reference, 'reason' => 'requested_by_customer'];
        if (! $full) {
            $itemId = $details['paddle_transaction_item_id'] ?? null;
            if (! is_string($itemId) || $itemId === '') {
                throw new \InvalidArgumentException('A Paddle transaction item ID is required for partial refunds.');
            }
            $payload['items'] = [['item_id' => $itemId, 'type' => 'partial', 'amount' => (string) $amountMinor]];
        }

        $response = $this->request('post', 'adjustments', $payload);
        $providerReference = data_get($response, 'data.id');
        if (! is_string($providerReference) || $providerReference === '') {
            throw new \RuntimeException('Paddle refund did not return an adjustment reference.');
        }

        return ['reference' => $providerReference];
    }

    /** @return array<string,mixed> */
    private function request(string $method, string $path, array $payload): array
    {
        $response = $this->http->acceptJson()->withToken((string) config('services.paddle.token'))->timeout(30)->retry(2, 250)->{$method}(rtrim((string) config('services.paddle.base_url', 'https://api.paddle.com'), '/').'/'.$path, $payload);
        if ($response->failed()) {
            throw new \RuntimeException('Paddle API returned HTTP '.$response->status().': '.substr($response->body(), 0, 500));
        }

        return $response->json();
    }
}
