<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Queries;

use Illuminate\Database\Eloquent\Collection;
use Liberu\Billing\CustomerPortal\Models\PortalRequest;

final class ListCustomerPortalRecords
{
    public function handle(int $teamId): Collection
    {
        return PortalRequest::query()->forTeam($teamId)->latest()->get();
    }
}
