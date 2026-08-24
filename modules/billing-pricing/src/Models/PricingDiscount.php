<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['team_id', 'code', 'kind', 'value', 'currency', 'starts_at', 'ends_at', 'max_redemptions', 'redemptions', 'active'])]
class PricingDiscount extends Model
{
    protected $table = 'billing_pricing_discounts';

    protected function casts(): array
    {
        return ['value' => 'integer', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'active' => 'boolean'];
    }
}
