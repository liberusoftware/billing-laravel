<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Liberu\Billing\Payments\Models\PaymentMandate;

final class PaymentMandateCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly PaymentMandate $paymentMandate) {}
}
