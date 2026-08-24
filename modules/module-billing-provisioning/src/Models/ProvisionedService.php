<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Provisioning\Enums\ProvisioningState;

/**
 * @property ProvisioningState $state
 */
#[Fillable(['team_id', 'customer_id', 'subscription_id', 'provider', 'external_id', 'state', 'last_error', 'metadata', 'last_reconciled_at'])]
class ProvisionedService extends Model
{
    protected $table = 'billing_provisioned_services';

    protected function casts(): array
    {
        return ['state' => ProvisioningState::class, 'metadata' => 'array', 'last_reconciled_at' => 'datetime'];
    }
}
