<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Policies;

use Liberu\Billing\Subscriptions\Models\Subscription;

final class SubscriptionPolicy
{
    public function viewAny(object $user): bool
    {
        return $this->access($user, 'read');
    }

    public function view(object $user, Subscription $subscription): bool
    {
        return $this->access($user, 'read') && ($subscription->team_id === null || (int) $subscription->team_id === (int) (data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id')));
    }

    public function create(object $user): bool
    {
        return $this->access($user, 'write');
    }

    public function update(object $user, Subscription $subscription): bool
    {
        $teamId = data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id');

        return $this->access($user, 'write') && ($subscription->team_id === null || (int) $subscription->team_id === (int) $teamId);
    }

    private function access(object $user, string $ability): bool
    {
        return ! method_exists($user, 'tokenCan') || $user->tokenCan("billing.subscriptions.$ability") || $user->tokenCan('*');
    }
}
