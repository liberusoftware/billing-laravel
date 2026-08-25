<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Contracts;

use Liberu\Billing\Payments\Models\Payment;

interface GatewayDriver
{
    /** @return array{reference:string} */
    public function capture(Payment $payment): array;

    /** @return array{reference:string} */
    public function refund(Payment $payment, int $amountMinor): array;
}
