<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Liberu\Billing\Invoicing\Models\Invoice;

final class InvoiceFinalized implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Invoice $invoice) {}
}
