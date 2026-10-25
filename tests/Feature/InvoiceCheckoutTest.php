<?php

declare(strict_types=1);

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\InvoiceCheckoutService;
use App\Services\PaymentGatewayService;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('reuses checkout payments and leaves invoices unpaid until provider settlement', function (): void {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['user_id' => $user->id]);
    $plan = SubscriptionPlan::query()->create(['name' => 'Hosting', 'code' => 'hosting', 'price' => 25, 'currency' => 'USD']);
    $subscription = Subscription::factory()->create(['customer_id' => $customer->id, 'subscription_plan_id' => $plan->id, 'renewal_period' => 'monthly']);
    $invoice = Invoice::factory()->create(['customer_id' => $customer->id, 'subscription_id' => $subscription->id, 'status' => 'pending', 'total_amount' => 25, 'currency' => 'USD']);
    $gateway = PaymentGateway::query()->create(['name' => 'Paddle', 'is_active' => true, 'api_key' => 'test-key', 'secret_key' => 'test-secret']);
    config()->set('services.paddle.webhook_gateway_id', $gateway->id);
    config()->set('services.paddle.invoice_prices', ['hosting' => ['monthly' => ['price_id' => 'pri_test', 'amount' => '25.00', 'currency' => 'USD']]]);
    $this->mock(PaymentGatewayService::class)->shouldReceive('processPayment')->twice()->withArgs(fn (Payment $payment) => $payment->invoice_id === $invoice->id && $payment->amount === 25.0)->andReturn(['checkout' => ['url' => 'https://checkout.paddle.com/test']]);
    $service = app(InvoiceCheckoutService::class);
    expect($service->paddleCheckoutUrl($invoice, $user))->toBe('https://checkout.paddle.com/test');
    $service->paddleCheckoutUrl($invoice, $user);
    expect(Payment::query()->where('invoice_id', $invoice->id)->count())->toBe(1)->and($invoice->fresh()->status)->toBe('pending');
});

it('rejects checkout by another customer before contacting the provider', function (): void {
    $invoice = Invoice::factory()->create(['status' => 'pending']);
    $this->mock(PaymentGatewayService::class)->shouldNotReceive('processPayment');
    expect(fn () => app(InvoiceCheckoutService::class)->paddleCheckoutUrl($invoice, User::factory()->create()))->toThrow(HttpException::class);
});
