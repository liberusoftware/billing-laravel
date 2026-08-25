<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Policies;

use Liberu\Billing\Invoicing\Models\InvoiceSchedule;

final class InvoiceSchedulePolicy
{
    public function viewAny(?object $user): bool
    {
        return $this->access($user, 'read');
    }

    public function create(?object $user): bool
    {
        return $this->access($user, 'write');
    }

    public function view(?object $user, InvoiceSchedule $schedule): bool
    {
        return $this->owns($user, $schedule, 'read');
    }

    public function update(?object $user, InvoiceSchedule $schedule): bool
    {
        return $this->owns($user, $schedule, 'write');
    }

    private function access(?object $user, string $ability): bool
    {
        $permission = "billing.invoicing.$ability";

        return $user !== null
            && ((method_exists($user, 'tokenCan') && ($user->tokenCan($permission) || $user->tokenCan('*')))
                || (method_exists($user, 'can') && $user->can($permission)));
    }

    private function owns(?object $user, InvoiceSchedule $schedule, string $ability): bool
    {
        if (! $this->access($user, $ability)) {
            return false;
        }

        $teamId = data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id');

        return $schedule->team_id === null || ($teamId !== null && (int) $schedule->team_id === (int) $teamId);
    }
}
