<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['team_id', 'code', 'name', 'decimal_places', 'enabled', 'exchange_rate'])]
class BillingCurrency extends Model
{
    protected $table = 'billing_currencies';

    protected function casts(): array
    {
        return ['decimal_places' => 'integer', 'enabled' => 'boolean', 'exchange_rate' => 'decimal:10'];
    }
}
