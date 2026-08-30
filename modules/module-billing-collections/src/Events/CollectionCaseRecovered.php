<?php

declare(strict_types=1);

namespace Liberu\Billing\Collections\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Liberu\Billing\Collections\Models\CollectionCase;

final class CollectionCaseRecovered implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly CollectionCase $case) {}
}
