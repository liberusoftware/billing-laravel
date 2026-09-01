<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Liberu\Billing\Catalog\Models\CatalogRecord;

final class CatalogRecordCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly CatalogRecord $record) {}
}
