<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Billing\Domains\Events\DomainDeleted;
use Liberu\Billing\Domains\Models\Domain;

final class DeleteDomain
{
    public function execute(Domain $domain): void
    {
        $domainId = $domain->getKey();
        $teamId = (int) $domain->team_id;

        DB::transaction(function () use ($domain, $domainId, $teamId): void {
            $locked = Domain::query()->lockForUpdate()->findOrFail($domain->getKey());
            $locked->delete();
            DomainDeleted::dispatch($domainId, $teamId);
        });
    }
}
