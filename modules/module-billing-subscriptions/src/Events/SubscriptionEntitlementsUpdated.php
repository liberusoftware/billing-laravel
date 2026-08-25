<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Liberu\Billing\Subscriptions\Models\Subscription;

final class SubscriptionEntitlementsUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Subscription $subscription) {}
}
