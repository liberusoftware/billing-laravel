<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Policies;

final class PricingRecordPolicy
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

        return $user !== null && ($record->team_id === null || ($team !== null && (int) $team === (int) $record->team_id));
    }
}
