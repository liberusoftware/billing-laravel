<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Liberu\Billing\Hosting\Models\HostingAccount;

final class HostingAccountPolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user !== null;
    }

    public function view(?Authenticatable $user, HostingAccount $record): bool
    {
        return $this->owns($user, $record->team_id);
    }

    public function create(?Authenticatable $user): bool
    {
        return $user !== null;
    }

    public function update(?Authenticatable $user, HostingAccount $record): bool
    {
        return $this->owns($user, $record->team_id);
    }

    private function owns(?Authenticatable $user, mixed $teamId): bool
    {
        $currentTeamId = data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id');

        return $user !== null && $currentTeamId !== null && (int) $currentTeamId === (int) $teamId;
    }
}
