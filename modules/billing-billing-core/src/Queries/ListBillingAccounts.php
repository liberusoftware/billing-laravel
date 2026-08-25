<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\Billing\Core\Models\BillingAccount;

final class ListBillingAccounts
{
    public function execute(?int $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return BillingAccount::query()
            ->when($teamId !== null, fn ($query) => $query->where('team_id', $teamId))
            ->latest('id')
            ->paginate(min(max($perPage, 1), 100));
    }
}
