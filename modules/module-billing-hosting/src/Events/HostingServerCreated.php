<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Liberu\Billing\Hosting\Models\HostingServer;

final class HostingServerCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly HostingServer $server) {}
}
