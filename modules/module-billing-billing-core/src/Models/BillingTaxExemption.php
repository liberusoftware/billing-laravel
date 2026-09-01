<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['team_id', 'customer_id', 'expires_at', 'enabled', 'reason'])]
class BillingTaxExemption extends Model
{
    protected $table = 'billing_tax_exemptions';

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'enabled' => 'boolean'];
    }
}
