<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Core\Enums\BillingAccountStatus;

#[Fillable(['team_id', 'name', 'currency', 'status', 'settings'])]
class BillingAccount extends Model
{
    protected $table = 'billing_accounts';

    protected function casts(): array
    {
        return [
            'status' => BillingAccountStatus::class,
            'settings' => 'array',
        ];
    }
}
