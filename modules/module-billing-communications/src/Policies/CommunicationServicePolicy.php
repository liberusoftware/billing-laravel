<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Liberu\Billing\Communications\Models\CommunicationService;

final class CommunicationServicePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $this->access($user, 'read');
    }

    public function create(?Authenticatable $user): bool
    {
        return $this->access($user, 'write');
    }

    public function view(?Authenticatable $user, CommunicationService $record): bool
    {
        return $this->access($user, 'read') && $this->owns($user, $record->team_id);
    }

    public function update(?Authenticatable $user, CommunicationService $record): bool
    {
        return $this->access($user, 'write') && $this->owns($user, $record->team_id);
    }

    private function owns(?Authenticatable $user, mixed $teamId): bool
    {
        $current = data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id');

        return $user !== null && $current !== null && (int) $current === (int) $teamId;
    }

    private function access(?Authenticatable $user, string $action): bool
    {
        $ability = "billing.communications.$action";

        return $user !== null && ((method_exists($user, 'tokenCan') && ($user->tokenCan($ability) || $user->tokenCan('*'))) || (method_exists($user, 'can') && $user->can($ability)));
    }
}
