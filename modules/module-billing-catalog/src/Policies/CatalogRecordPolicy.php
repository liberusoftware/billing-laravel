<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Policies;

use Liberu\Billing\Catalog\Models\CatalogRecord;

final class CatalogRecordPolicy
{
    public function viewAny(object $user): bool
    {
        return $this->access($user);
    }

    public function view(object $user, CatalogRecord $record): bool
    {
        return $this->access($user) && ($record->team_id === null || (int) $record->team_id === (int) (data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id')));
    }

    public function create(object $user): bool
    {
        return $this->writeAccess($user);
    }

    public function update(object $user, CatalogRecord $record): bool
    {
        return $this->writeAccess($user) && ($record->team_id === null || (int) $record->team_id === (int) (data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id')));
    }

    private function access(object $user): bool
    {
        return ! method_exists($user, 'tokenCan') || $user->tokenCan('billing.catalog.write') || $user->tokenCan('billing.catalog.read') || $user->tokenCan('*');
    }

    private function writeAccess(object $user): bool
    {
        return ! method_exists($user, 'tokenCan') || $user->tokenCan('billing.catalog.write') || $user->tokenCan('*');
    }
}
