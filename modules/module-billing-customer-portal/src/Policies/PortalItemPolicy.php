<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Policies;

final class PortalItemPolicy
{
    public function viewAny(?object $user): bool
    {
        return $user !== null;
    }

    public function create(?object $user): bool
    {
        return $user !== null;
    }

    public function view(?object $user, object $item): bool
    {
        $team = data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id');

        return $user !== null && $team !== null && (int) $team === (int) $item->team_id;
    }
}
