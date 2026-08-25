<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Policies;

final class ProvisioningPolicy
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
        $recordTeam = $record->team_id ?? $record->service?->team_id;

        return $this->access($user, 'read') && ($recordTeam === null || ($team !== null && (int) $team === (int) $recordTeam));
    }

    public function update(?object $user, object $record): bool
    {
        $team = data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id');
        $recordTeam = $record->team_id ?? $record->service?->team_id;

        return $this->access($user, 'write') && ($recordTeam === null || ($team !== null && (int) $team === (int) $recordTeam));
    }

    private function access(?object $user, string $action): bool
    {
        $ability = "billing.provisioning.$action";

        return $user !== null && ((! method_exists($user, 'tokenCan')) || $user->tokenCan($ability) || $user->tokenCan('*') || (method_exists($user, 'can') && $user->can($ability)));
    }
}
