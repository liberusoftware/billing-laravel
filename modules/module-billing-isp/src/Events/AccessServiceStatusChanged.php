<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Liberu\Billing\Isp\Models\AccessService;

final class AccessServiceStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly AccessService $service) {}
}
