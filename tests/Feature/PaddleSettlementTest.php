<?php

declare(strict_types=1);

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\PaymentHistory;

beforeEach(function (): void {
    $this->invoice = Invoice::factory()->create(['status' => 'pending', 'total_amount' => 25, 'currency' => 'USD']);
    $this->gateway = PaymentGateway::query()->create(['name' => 'Paddle', 'is_active' => true, 'api_key' => 'test-key', 'secret_key' => 'test-secret']);
    $this->payment = new Payment();
    $this->payment->forceFill([
        'invoice_id' => $this->invoice->id, 'customer_id' => $this->invoice->customer_id,
        'team_id' => $this->invoice->team_id, 'payment_gateway_id' => $this->gateway->id,
        'amount' => 25, 'currency' => 'USD', 'payment_date' => now(), 'payment_method' => 'paddle',
        'status' => 'pending', 'transaction_id' => 'txn_settlement',
    ])->save();
    config()->set('services.paddle.webhook_secret', 'notification-secret');
    config()->set('services.paddle.webhook_gateway_id', $this->gateway->id);
    $this->payload = ['event_type' => 'transaction.completed', 'data' => [
        'id' => 'txn_settlement', 'status' => 'completed', 'currency_code' => 'USD',
        'custom_data' => ['billing_payment_id' => $this->payment->id],
        'details' => ['totals' => ['grand_total' => '2500']],
    ]];
    $this->deliver = function (array $payload, ?int $timestamp = null, string $secret = 'notification-secret') {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp ??= now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.':'.$body, $secret);

        return $this->call('POST', '/api/webhooks/paddle/settlement', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
            'HTTP_PADDLE_SIGNATURE' => 'ts='.$timestamp.';h1='.$signature,
        ], $body);
    };
});

it('settles a verified payment once across duplicate deliveries', function (): void {
    ($this->deliver)($this->payload)->assertOk();
    ($this->deliver)($this->payload)->assertOk();
    expect($this->payment->fresh()->status)->toBe('completed')
        ->and($this->invoice->fresh()->status)->toBe('paid')
        ->and(PaymentHistory::query()->where('payment_id', $this->payment->id)->count())->toBe(1);
});

it('rejects invalid signatures and old or future notification timestamps', function (): void {
    ($this->deliver)($this->payload, null, 'incorrect-secret')->assertUnauthorized();
    ($this->deliver)($this->payload, now()->subMinute()->timestamp)->assertUnauthorized();
    ($this->deliver)($this->payload, now()->addMinute()->timestamp)->assertUnauthorized();
    expect($this->invoice->fresh()->status)->toBe('pending')->and($this->payment->fresh()->status)->toBe('pending');
});

it('rejects incorrect totals currencies and provider references', function (string $field, mixed $value): void {
    data_set($this->payload, $field, $value);
    ($this->deliver)($this->payload)->assertUnprocessable();
    expect($this->invoice->fresh()->status)->toBe('pending')->and($this->payment->fresh()->status)->toBe('pending');
})->with([
    ['data.details.totals.grand_total', '2499'], ['data.currency_code', 'EUR'], ['data.id', 'txn_another'],
]);

it('cannot settle a payment through another gateway or customer', function (): void {
    config()->set('services.paddle.webhook_gateway_id', $this->gateway->id + 1);
    ($this->deliver)($this->payload)->assertUnprocessable();
    config()->set('services.paddle.webhook_gateway_id', $this->gateway->id);
    $other = Invoice::factory()->create();
    $this->payment->forceFill(['customer_id' => $other->customer_id])->save();
    ($this->deliver)($this->payload)->assertUnprocessable();
    expect($this->invoice->fresh()->status)->toBe('pending');
});

it('acknowledges signed non-completion events without settling an invoice', function (): void {
    $this->payload['event_type'] = 'transaction.ready';
    ($this->deliver)($this->payload)->assertOk();
    expect($this->payment->fresh()->status)->toBe('pending')->and($this->invoice->fresh()->status)->toBe('pending');
});

it('uses the provider currency units for zero-decimal payments', function (): void {
    $this->invoice->update(['currency' => 'JPY', 'total_amount' => 2500]);
    $this->payment->forceFill(['currency' => 'JPY', 'amount' => 2500])->save();
    $this->payload['data']['currency_code'] = 'JPY';
    ($this->deliver)($this->payload)->assertOk();
    expect($this->invoice->fresh()->status)->toBe('paid');
});

it('rejects fractional zero-decimal payments and refunded transactions', function (): void {
    $this->invoice->update(['currency' => 'JPY', 'total_amount' => 25.50]);
    $this->payment->forceFill(['currency' => 'JPY', 'amount' => 25.50])->save();
    $this->payload['data']['currency_code'] = 'JPY';
    $this->payload['data']['details']['totals']['grand_total'] = '25';
    ($this->deliver)($this->payload)->assertUnprocessable();
    $this->invoice->update(['currency' => 'USD', 'total_amount' => 25]);
    $this->payment->forceFill(['currency' => 'USD', 'amount' => 25, 'refunded_amount' => 1])->save();
    $this->payload['data']['currency_code'] = 'USD';
    $this->payload['data']['details']['totals']['grand_total'] = '2500';
    ($this->deliver)($this->payload)->assertUnprocessable();
    expect($this->invoice->fresh()->status)->toBe('pending');
});

it('requires a configured signing secret and a signed notification', function (): void {
    $this->postJson('/api/webhooks/paddle/settlement', $this->payload)->assertUnauthorized();
    config()->set('services.paddle.webhook_secret', '');
    ($this->deliver)($this->payload)->assertUnauthorized();
});

it('accepts a valid signature alongside a rotated signature', function (): void {
    $body = json_encode($this->payload, JSON_THROW_ON_ERROR);
    $timestamp = now()->timestamp;
    $signature = hash_hmac('sha256', $timestamp.':'.$body, 'notification-secret');
    $this->call('POST', '/api/webhooks/paddle/settlement', [], [], [], [
        'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
        'HTTP_PADDLE_SIGNATURE' => 'ts='.$timestamp.';h1='.str_repeat('a', 64).';h1='.$signature,
    ], $body)->assertOk();
});
