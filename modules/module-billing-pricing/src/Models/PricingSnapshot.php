<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['team_id', 'pricing_plan_id', 'version', 'payload', 'captured_at'])]
class PricingSnapshot extends Model
{
    protected $table = 'billing_pricing_snapshots';

    protected function casts(): array
    {
        return ['payload' => 'array', 'captured_at' => 'datetime'];
    }
}
