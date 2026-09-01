<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['team_id', 'pricing_plan_id', 'customer_id', 'starts_at', 'ends_at', 'terms', 'status'])]
class PricingContract extends Model
{
    protected $table = 'billing_pricing_contracts';

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'terms' => 'array'];
    }
}
