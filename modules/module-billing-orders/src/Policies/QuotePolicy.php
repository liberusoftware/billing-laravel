<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Policies;

use Liberu\Billing\Orders\Models\Quote;

final class QuotePolicy
{
    public function viewAny(object $user): bool
    {
        return $this->access($user);
    }

    public function view(object $user, Quote $quote): bool
    {
        return $this->access($user) && $this->sameTeam($user, $quote);
    }

    public function create(object $user): bool
    {
        return $this->write($user);
    }

    public function update(object $user, Quote $quote): bool
    {
        return $this->write($user) && $this->sameTeam($user, $quote);
    }

    private function sameTeam(object $user, Quote $quote): bool
    {
        return $quote->team_id === null || (int) $quote->team_id === (int) (data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id'));
    }

    private function access(object $user): bool
    {
        return ! method_exists($user, 'tokenCan') || $user->tokenCan('billing.orders.read') || $user->tokenCan('billing.orders.write') || $user->tokenCan('*');
    }

    private function write(object $user): bool
    {
        return ! method_exists($user, 'tokenCan') || $user->tokenCan('billing.orders.write') || $user->tokenCan('*');
    }
}
