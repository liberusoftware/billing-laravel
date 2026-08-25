<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Queries;

use Illuminate\Database\Eloquent\Collection;
use Liberu\Billing\Provisioning\Models\ProvisionedService;

final class ListProvisionedServices
{
    /** @return Collection<int, ProvisionedService> */
    public function execute(?int $teamId = null): Collection
    {
        return ProvisionedService::query()
            ->where(fn ($query) => $teamId === null
                ? $query->whereNull('team_id')
                : $query->whereNull('team_id')->orWhere('team_id', $teamId))
            ->latest()
            ->get();
    }
}
