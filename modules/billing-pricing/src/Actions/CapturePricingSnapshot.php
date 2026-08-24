<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Pricing\Models\PricingPlan;
use Liberu\Billing\Pricing\Models\PricingSnapshot;

final readonly class CapturePricingSnapshot
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(PricingPlan $plan): PricingSnapshot
    {
        return $this->database->transaction(function () use ($plan): PricingSnapshot {
            $version = ((int) PricingSnapshot::query()->where('pricing_plan_id', $plan->getKey())->max('version')) + 1;

            return PricingSnapshot::query()->create(['team_id' => $plan->team_id, 'pricing_plan_id' => $plan->getKey(), 'version' => $version, 'payload' => $plan->toArray(), 'captured_at' => now()]);
        });
    }
}
