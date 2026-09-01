<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Pricing\Enums\PricingModel;
use Liberu\Billing\Pricing\Enums\PricingPlanStatus;

#[Fillable(['team_id', 'product_id', 'name', 'pricing_model', 'currency', 'unit_amount_minor', 'billing_interval', 'usage_unit', 'tiers', 'metadata', 'status'])]
class PricingPlan extends Model
{
    protected $table = 'billing_pricing_plans';

    protected function casts(): array
    {
        return ['pricing_model' => PricingModel::class, 'status' => PricingPlanStatus::class, 'unit_amount_minor' => 'integer', 'tiers' => 'array', 'metadata' => 'array'];
    }
}
