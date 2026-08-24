<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Policies;

final class HostingCapabilityPolicy
{
    public function viewAny(?object $user): bool
    {
        return $this->access($user, 'read');
    }

    public function create(?object $user): bool
    {
        return $this->access($user, 'write');
    }

    public function view(?object $user, object $record): bool
    {
        $team = data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id');

        return $this->access($user, 'read') && $team !== null && (int) $team === (int) $record->team_id;
    }

    public function update(?object $user, object $record): bool
    {
        $team = data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id');

        return $this->access($user, 'write') && $user !== null && $team !== null && (int) $team === (int) $record->team_id;
    }

    private function access(?object $user, string $action): bool
    {
        $ability = "billing.hosting.$action";

        return $user !== null && ((! method_exists($user, 'tokenCan')) || $user->tokenCan($ability) || $user->tokenCan('*') || (method_exists($user, 'can') && $user->can($ability)));
    }
}
