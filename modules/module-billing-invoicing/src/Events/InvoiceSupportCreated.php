<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Liberu\Billing\Invoicing\Models\InvoiceSupport;

final class InvoiceSupportCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly InvoiceSupport $support) {}
}
