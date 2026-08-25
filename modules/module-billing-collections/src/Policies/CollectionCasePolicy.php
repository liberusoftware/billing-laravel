<?php

declare(strict_types=1);

namespace Liberu\Billing\Collections\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Liberu\Billing\Collections\Models\CollectionCase;

final class CollectionCasePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user !== null && ($user->tokenCan('billing.collections.read') || $user->can('billing.collections.read'));
    }

    public function create(?Authenticatable $user): bool
    {
        return $user !== null && ($user->tokenCan('billing.collections.write') || $user->can('billing.collections.write'));
    }

    public function update(?Authenticatable $user, CollectionCase $case): bool
    {
        $teamId = data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id');

        return $this->create($user) && ($case->team_id === null || (int) $case->team_id === (int) $teamId);
    }
}
