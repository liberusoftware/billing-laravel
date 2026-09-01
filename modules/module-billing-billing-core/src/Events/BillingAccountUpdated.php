<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Liberu\Billing\Core\Models\BillingAccount;

final class BillingAccountUpdated implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly BillingAccount $account) {}
}
