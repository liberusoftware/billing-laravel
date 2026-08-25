<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Policies;

final class PaymentRecordPolicy
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
        $team = data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id');
        $recordTeam = $record->team_id ?? $record->payment?->team_id;

        return $this->access($user, 'read') && ($recordTeam === null || ($team !== null && (int) $team === (int) $recordTeam));
    }

    private function access(?object $user, string $action): bool
    {
        $ability = "billing.payments.$action";

        return $user !== null && ((! method_exists($user, 'tokenCan')) || $user->tokenCan($ability) || $user->tokenCan('*') || (method_exists($user, 'can') && $user->can($ability)));
    }
}
