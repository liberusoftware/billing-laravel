<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Policies;

use Liberu\Billing\Core\Models\BillingAccount;

final class BillingAccountPolicy
{
    public function viewAny(object $user): bool
    {
        return $this->hasBillingAccess($user);
    }

    public function view(object $user, BillingAccount $account): bool
    {
        return $this->hasBillingAccess($user) && $this->ownsTeamRecord($user, $account);
    }

    public function create(object $user): bool
    {
        return $this->hasBillingAccess($user);
    }

    public function update(object $user, BillingAccount $account): bool
    {
        return $this->view($user, $account);
    }

    public function delete(object $user, BillingAccount $account): bool
    {
        return $this->view($user, $account) && $account->status->value !== 'closed';
    }

    private function hasBillingAccess(object $user): bool
    {
        return method_exists($user, 'tokenCan')
            ? $user->tokenCan('billing.billing-core.read')
                || $user->tokenCan('billing.billing-core.write')
                || $user->tokenCan('*')
            : true;
    }

    private function ownsTeamRecord(object $user, BillingAccount $account): bool
    {
        if ($account->team_id === null) {
            return true;
        }

        return (int) data_get($user, 'current_team_id') === (int) $account->team_id
            || (int) data_get($user, 'currentTeam.id') === (int) $account->team_id;
    }
}
