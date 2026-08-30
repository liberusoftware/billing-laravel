<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Liberu\Billing\CustomerPortal\Models\PortalItem;

final class PortalItemCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly PortalItem $item) {}
}
