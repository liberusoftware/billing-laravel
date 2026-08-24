<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Liberu\Billing\Usage\Models\Meter;

final class UsagePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user !== null && ($user->tokenCan('billing.usage.read') || $user->can('billing.usage.read'));
    }

    public function create(?Authenticatable $user): bool
    {
        return $user !== null && ($user->tokenCan('billing.usage.write') || $user->can('billing.usage.write'));
    }

    public function update(?Authenticatable $user, Meter $meter): bool
    {
        $teamId = data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id');

        return $this->create($user) && ($meter->team_id === null || (int) $meter->team_id === (int) $teamId);
    }
}
