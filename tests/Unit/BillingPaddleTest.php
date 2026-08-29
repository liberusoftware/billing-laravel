<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Liberu\Billing\Paddle\PaddleGatewayDriver;
use Liberu\Billing\Payments\Models\Payment;

it('captures a Paddle transaction through the provider-neutral gateway contract', function (): void {
    config()->set('services.paddle.token', 'pdl_test_token');
    config()->set('services.paddle.base_url', 'https://paddle.test');
    Http::fake(['https://paddle.test/transactions' => Http::response([
        'data' => ['id' => 'txn_123', 'status' => 'completed'],
    ])]);

    $payment = new Payment(['metadata' => [
        'paddle_price_id' => 'pri_123abc',
        'paddle_quantity' => 2,
        'paddle_customer_id' => 'ctm_123',
    ]]);
    $payment->id = 42;

    expect(app(PaddleGatewayDriver::class)->capture($payment))->toBe(['reference' => 'txn_123']);

    Http::assertSent(function (Request $request): bool {
        return $request->hasHeader('Authorization', 'Bearer pdl_test_token')
            && $request->url() === 'https://paddle.test/transactions'
            && $request['items'] === [['price_id' => 'pri_123abc', 'quantity' => 2]]
            && $request['custom_data'] === ['billing_payment_id' => '42'];
    });
});

it('supports full and partial Paddle refunds', function (): void {
    config()->set('services.paddle.token', 'pdl_test_token');
    Http::fake(['https://api.paddle.com/adjustments' => Http::sequence()
        ->push(['data' => ['id' => 'adj_full']])
        ->push(['data' => ['id' => 'adj_partial']])]);

    $driver = app(PaddleGatewayDriver::class);
    $payment = new Payment([
        'amount_minor' => 1000,
        'provider_reference' => 'txn_123',
        'metadata' => ['paddle_transaction_item_id' => 'item_123'],
    ]);

    expect($driver->refund($payment, 1000))->toBe(['reference' => 'adj_full'])
        ->and($driver->refund($payment, 400))->toBe(['reference' => 'adj_partial']);

    Http::assertSentCount(2);
    Http::assertSent(function (Request $request): bool {
        return $request['type'] === 'partial'
            && $request['items'] === [['item_id' => 'item_123', 'type' => 'partial', 'amount' => '400']];
    });
});

it('rejects invalid Paddle metadata and incomplete provider responses', function (): void {
    Http::fake(['https://api.paddle.com/transactions' => Http::response([
        'data' => ['status' => 'completed'],
    ])]);
    $driver = app(PaddleGatewayDriver::class);
    $payment = new Payment(['metadata' => ['paddle_price_id' => 'invalid']]);

    expect(fn () => $driver->capture($payment))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $driver->capture(new Payment(['metadata' => ['paddle_price_id' => 'pri_valid']])))->toThrow(RuntimeException::class);
});

it('does not treat a checkout-ready Paddle transaction as captured', function (): void {
    Http::fake(['https://api.paddle.com/transactions' => Http::response([
        'data' => ['id' => 'txn_ready', 'status' => 'ready'],
    ])]);

    expect(fn () => app(PaddleGatewayDriver::class)->capture(new Payment([
        'metadata' => ['paddle_price_id' => 'pri_valid'],
    ])))->toThrow(RuntimeException::class, 'Paddle transaction is not complete: ready');
});
