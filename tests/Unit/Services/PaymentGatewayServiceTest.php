<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\PaymentGatewayService;
use Tests\TestCase;

class PaymentGatewayServiceTest extends TestCase
{
    private PaymentGatewayService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PaymentGatewayService::class);
    }

    public function test_validate_payment_method_accepts_supported_methods(): void
    {
        $supportedMethods = [
            'credit_card',
            'debit_card',
            'paypal',
            'google_pay',
            'apple_pay',
            'square',
            'bank_transfer',
        ];

        foreach ($supportedMethods as $method) {
            $this->assertTrue(
                $this->service->validatePaymentMethod($method),
                "Method '{$method}' should be supported."
            );
        }
    }

    public function test_validate_payment_method_rejects_unsupported_methods(): void
    {
        $this->assertFalse($this->service->validatePaymentMethod('bitcoin'));
        $this->assertFalse($this->service->validatePaymentMethod('cash'));
        $this->assertFalse($this->service->validatePaymentMethod(''));
    }

    public function test_service_instantiates_correctly(): void
    {
        $this->assertInstanceOf(PaymentGatewayService::class, $this->service);
    }
}
