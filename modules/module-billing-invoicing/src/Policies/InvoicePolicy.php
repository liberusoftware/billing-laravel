<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Liberu\Billing\Invoicing\Models\Invoice;

final class InvoicePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return $user !== null && ($user->tokenCan('billing.invoicing.read') || $user->can('billing.invoicing.read'));
    }

    public function view(?Authenticatable $user, Invoice $invoice): bool
    {
        return $this->owns($user, $invoice);
    }

    public function create(?Authenticatable $user): bool
    {
        return $user !== null && ($user->tokenCan('billing.invoicing.write') || $user->can('billing.invoicing.write'));
    }

    public function update(?Authenticatable $user, Invoice $invoice): bool
    {
        return $this->owns($user, $invoice) && $this->create($user);
    }

    private function owns(?Authenticatable $user, Invoice $invoice): bool
    {
        if (! $user || ! $this->create($user)) {
            return false;
        }
        $teamId = data_get($user, 'current_team_id') ?? data_get($user, 'currentTeam.id');

        return $invoice->team_id === null || (int) $invoice->team_id === (int) $teamId;
    }
}
