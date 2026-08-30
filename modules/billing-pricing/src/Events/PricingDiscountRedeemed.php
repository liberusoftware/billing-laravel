<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Liberu\Billing\Pricing\Models\PricingDiscount;

final class PricingDiscountRedeemed implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly PricingDiscount $discount) {}
}
