<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Liberu\Billing\Orders\Models\Quote;

final class QuoteStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Quote $quote, public readonly string $from, public readonly string $to) {}
}
