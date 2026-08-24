<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Liberu\Billing\Isp\Models\AccessService;

final class AccessServicePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user !== null;
    }

    public function create(?Authenticatable $user): bool
    {
        return $user !== null;
    }

    public function view(?Authenticatable $user, AccessService $record): bool
    {
        return $this->owns($user, $record->team_id);
    }

    public function update(?Authenticatable $user, AccessService $record): bool
    {
        return $this->owns($user, $record->team_id);
    }

    private function owns(?Authenticatable $user, mixed $teamId): bool
    {
        $current = data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id');

        return $user !== null && $current !== null && (int) $current === (int) $teamId;
    }
}
