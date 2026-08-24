<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Policies;

final class DomainSupportPolicy
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
        return $this->access($user, 'read') && $this->owns($user, $record);
    }

    public function update(?object $user, object $record): bool
    {
        return $this->access($user, 'write') && $this->owns($user, $record);
    }

    private function owns(?object $user, object $record): bool
    {
        $team = data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id');

        return $user !== null && $team !== null && (int) $team === (int) $record->team_id;
    }

    private function access(?object $user, string $action): bool
    {
        $ability = "billing.domains.$action";

        return $user !== null && ((method_exists($user, 'tokenCan') && ($user->tokenCan($ability) || $user->tokenCan('*'))) || (method_exists($user, 'can') && $user->can($ability)));
    }
}
