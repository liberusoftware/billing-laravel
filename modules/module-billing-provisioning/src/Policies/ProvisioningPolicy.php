<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Policies;

final class ProvisioningPolicy
{
    public function viewAny(?object $user): bool
    {
        return $user !== null;
    }

    public function create(?object $user): bool
    {
        return $user !== null;
    }

    public function view(?object $user, object $record): bool
    {
        $team = data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id');
        $recordTeam = $record->team_id ?? $record->service?->team_id;

        return $user !== null && ($recordTeam === null || ($team !== null && (int) $team === (int) $recordTeam));
    }
}
