<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Policies;

use Illuminate\Database\Eloquent\Model;

final class BillingCoreRecordPolicy
{
    public function viewAny(object $user): bool
    {
        return $this->access($user, 'read');
    }

    public function view(object $user, Model $record): bool
    {
        return $this->access($user, 'read') && $this->sameTeam($user, $record->getAttribute('team_id'));
    }

    public function create(object $user): bool
    {
        return $this->access($user, 'write');
    }

    public function update(object $user, Model $record): bool
    {
        return $this->access($user, 'write') && $this->sameTeam($user, $record->getAttribute('team_id'));
    }

    public function delete(object $user, Model $record): bool
    {
        return $this->update($user, $record);
    }

    private function access(object $user, string $ability): bool
    {
        $permission = "billing.billing-core.$ability";

        return (method_exists($user, 'tokenCan') && ($user->tokenCan($permission) || $user->tokenCan('*')))
            || (method_exists($user, 'can') && $user->can($permission));
    }

    private function sameTeam(object $user, mixed $teamId): bool
    {
        return $teamId === null || (int) $teamId === (int) (data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id'));
    }
}
