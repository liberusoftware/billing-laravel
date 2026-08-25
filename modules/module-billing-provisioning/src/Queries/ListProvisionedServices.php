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
        return ProvisionedService::query()->when($teamId !== null, fn ($query) => $query->where('team_id', $teamId))->latest()->get();
    }
}
