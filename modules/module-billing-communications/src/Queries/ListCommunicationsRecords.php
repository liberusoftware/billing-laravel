<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Queries;

use Illuminate\Database\Eloquent\Collection;
use Liberu\Billing\Communications\Models\CommunicationService;

final class ListCommunicationsRecords
{
    public function handle(int $teamId): Collection
    {
        return CommunicationService::query()->forTeam($teamId)->latest()->get();
    }
}
