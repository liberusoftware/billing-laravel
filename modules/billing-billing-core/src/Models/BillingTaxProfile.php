<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['team_id', 'name', 'rate', 'jurisdiction', 'inclusive', 'enabled', 'threshold_amount', 'threshold_rate'])]
class BillingTaxProfile extends Model
{
    protected $table = 'billing_tax_profiles';

    protected function casts(): array
    {
        return ['rate' => 'decimal:5', 'threshold_amount' => 'decimal:6', 'threshold_rate' => 'decimal:5', 'inclusive' => 'boolean', 'enabled' => 'boolean'];
    }
}
