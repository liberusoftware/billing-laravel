<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Policies;

use Liberu\Billing\Payments\Models\PaymentMandate;

final class PaymentMandatePolicy
{
    public function viewAny(object $user): bool
    {
        return $this->access($user, 'read');
    }

    public function view(object $user, PaymentMandate $mandate): bool
    {
        return $this->access($user, 'read') && $this->sameTeam($user, $mandate->team_id);
    }

    public function create(object $user): bool
    {
        return $this->access($user, 'write');
    }

    private function access(object $user, string $ability): bool
    {
        return ! method_exists($user, 'tokenCan') || $user->tokenCan("billing.payments.$ability") || $user->tokenCan('*');
    }

    private function sameTeam(object $user, mixed $teamId): bool
    {
        return $teamId === null || (int) $teamId === (int) (data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id'));
    }
}
