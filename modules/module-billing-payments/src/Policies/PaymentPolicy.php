<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Policies;

use Liberu\Billing\Payments\Models\Payment;

final class PaymentPolicy
{
    public function viewAny(object $user): bool
    {
        return $this->access($user, 'read');
    }

    public function view(object $user, Payment $payment): bool
    {
        $teamId = data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id');

        return $this->access($user, 'read') && ($payment->team_id === null || (int) $payment->team_id === (int) $teamId);
    }

    public function create(object $user): bool
    {
        return $this->access($user, 'write');
    }

    public function update(object $user, Payment $payment): bool
    {
        $teamId = data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id');

        return $this->access($user, 'write') && ($payment->team_id === null || (int) $payment->team_id === (int) $teamId);
    }

    private function access(object $user, string $ability): bool
    {
        return ! method_exists($user, 'tokenCan') || $user->tokenCan("billing.payments.$ability") || $user->tokenCan('*');
    }
}
