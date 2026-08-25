<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\Billing\CustomerPortal\Models\PortalRequest;

final class ListCustomerPortalRecords
{
    public function handle(int $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return PortalRequest::query()->forTeam($teamId)->latest()->paginate(min(max($perPage, 1), 100));
    }
}
