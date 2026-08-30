<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Liberu\Billing\Communications\Models\CommunicationService;

final class CommunicationServiceCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly CommunicationService $service) {}
}
