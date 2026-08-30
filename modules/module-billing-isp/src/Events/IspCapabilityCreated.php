<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Liberu\Billing\Isp\Models\IspCapability;

final class IspCapabilityCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly IspCapability $capability) {}
}
