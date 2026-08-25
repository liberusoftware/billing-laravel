<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\CustomerPortal\Models\PortalItem;

final class TransitionPortalItem
{
    public function handle(PortalItem $item, string $status): PortalItem
    {
        if (! in_array($status, ['open', 'in_progress', 'completed', 'cancelled', 'failed'], true)) {
            throw new InvalidArgumentException('Portal item status is invalid.');
        }

        return DB::transaction(function () use ($item, $status): PortalItem {
            $item->update(['status' => $status]);

            return $item->refresh();
        });
    }
}
