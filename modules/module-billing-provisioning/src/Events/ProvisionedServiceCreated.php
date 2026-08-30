<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Liberu\Billing\Provisioning\Models\ProvisionedService;

final class ProvisionedServiceCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly ProvisionedService $service) {}
}
