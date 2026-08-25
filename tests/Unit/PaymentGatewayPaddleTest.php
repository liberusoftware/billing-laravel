<?php

declare(strict_types=1);

use App\Services\PaymentGatewayService;

it('recognizes Paddle as a supported payment method', function (): void {
    expect(app(PaymentGatewayService::class)->validatePaymentMethod('paddle'))->toBeTrue();
});

it('rejects unknown payment methods', function (): void {
    expect(app(PaymentGatewayService::class)->validatePaymentMethod('unknown'))->toBeFalse();
});
