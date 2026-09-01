<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Liberu\Billing\Pricing\Models\PricingPlan;

final class PricingPlanCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly PricingPlan $plan) {}
}
