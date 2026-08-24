<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\Billing\CustomerPortal\Models\PortalItem;

final class ListPortalItems
{
    public function handle(int $teamId, ?string $type = null): LengthAwarePaginator
    {
        return PortalItem::query()->where('team_id', $teamId)->when($type !== null, fn ($query) => $query->where('type', $type))->latest()->paginate(25);
    }
}
