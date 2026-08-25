<?php

declare(strict_types=1);

namespace Liberu\Billing\Stripe;

use Liberu\Billing\Payments\Contracts\GatewayDriver;
use Liberu\Billing\Payments\Models\Payment;
use Stripe\StripeClient;

final readonly class StripeGatewayDriver implements GatewayDriver
{
    public function __construct(private StripeClient $client) {}

    public function capture(Payment $payment): array
    {
        $token = data_get($payment->metadata, 'stripe_payment_method') ?? data_get($payment->metadata, 'stripe_token');
        if (! is_string($token) || $token === '') {
            throw new \InvalidArgumentException('A Stripe payment method is required.');
        }

        $intent = $this->client->paymentIntents->create([
            'amount' => (int) $payment->amount_minor,
            'currency' => strtolower($payment->currency),
            'payment_method' => $token,
            'confirm' => true,
            'description' => 'Billing payment #'.$payment->getKey(),
            'metadata' => ['billing_payment_id' => (string) $payment->getKey()],
        ]);

        if (! in_array($intent->status, ['succeeded', 'requires_capture'], true)) {
            throw new \RuntimeException('Stripe payment did not complete: '.$intent->status);
        }

        return ['reference' => (string) $intent->id];
    }

    public function refund(Payment $payment, int $amountMinor): array
    {
        $reference = (string) $payment->provider_reference;
        if ($reference === '') {
            throw new \InvalidArgumentException('A Stripe payment reference is required for refunds.');
        }

        $refund = $this->client->refunds->create(['payment_intent' => $reference, 'amount' => $amountMinor, 'metadata' => ['billing_payment_id' => (string) $payment->getKey()]]);

        return ['reference' => (string) $refund->id];
    }
}
