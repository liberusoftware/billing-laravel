<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Policies;

use Liberu\Billing\Orders\Models\Order;

final class OrderPolicy
{
    public function viewAny(object $user): bool
    {
        return $this->access($user);
    }

    public function view(object $user, Order $order): bool
    {
        return $this->access($user) && ($order->team_id === null || (int) $order->team_id === (int) (data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id')));
    }

    public function create(object $user): bool
    {
        return $this->access($user);
    }

    public function update(object $user, Order $order): bool
    {
        return $this->writeAccess($user) && ($order->team_id === null || (int) $order->team_id === (int) (data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id')));
    }

    private function access(object $user): bool
    {
        return ! method_exists($user, 'tokenCan') || $user->tokenCan('billing.orders.read') || $user->tokenCan('billing.orders.write') || $user->tokenCan('*');
    }

    private function writeAccess(object $user): bool
    {
        return ! method_exists($user, 'tokenCan') || $user->tokenCan('billing.orders.write') || $user->tokenCan('*');
    }
}
