<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Queries;

use Illuminate\Database\Eloquent\Collection;
use Liberu\Billing\Hosting\Models\HostingAccount;

final class ListHostingRecords
{
    public function handle(int $teamId): Collection
    {
        return HostingAccount::query()->forTeam($teamId)->latest()->get();
    }
}
