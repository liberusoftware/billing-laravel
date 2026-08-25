<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Policies;

use Liberu\Billing\Pricing\Models\PricingPlan;

final class PricingPlanPolicy
{
    public function viewAny(object $user): bool
    {
        return $this->access($user);
    }

    public function view(object $user, PricingPlan $plan): bool
    {
        return $this->access($user) && ($plan->team_id === null || (int) $plan->team_id === (int) (data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id')));
    }

    public function create(object $user): bool
    {
        return $this->writeAccess($user);
    }

    public function update(object $user, PricingPlan $plan): bool
    {
        $teamId = data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id');

        return $this->writeAccess($user)
            && ($plan->team_id === null || ($teamId !== null && (int) $plan->team_id === (int) $teamId));
    }

    private function access(object $user): bool
    {
        return ! method_exists($user, 'tokenCan') || $user->tokenCan('billing.pricing.read') || $user->tokenCan('billing.pricing.write') || $user->tokenCan('*');
    }

    private function writeAccess(object $user): bool
    {
        return ! method_exists($user, 'tokenCan') || $user->tokenCan('billing.pricing.write') || $user->tokenCan('*');
    }
}
