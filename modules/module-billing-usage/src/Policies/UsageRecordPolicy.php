<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Policies;

final class UsageRecordPolicy
{
    public function viewAny(?object $user): bool
    {
        return $this->access($user, 'read');
    }

    public function view(?object $user, object $record): bool
    {
        $team = data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id');

        return $this->access($user, 'read') && ($record->team_id === null || ($team !== null && (int) $team === (int) $record->team_id));
    }

    public function create(?object $user): bool
    {
        return $this->access($user, 'write');
    }

    private function access(?object $user, string $action): bool
    {
        $ability = "billing.usage.$action";

        return $user !== null && ((! method_exists($user, 'tokenCan')) || $user->tokenCan($ability) || $user->tokenCan('*') || (method_exists($user, 'can') && $user->can($ability)));
    }
}
