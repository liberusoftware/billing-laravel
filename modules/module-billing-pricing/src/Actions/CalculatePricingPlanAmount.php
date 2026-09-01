<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Actions;

use Liberu\Billing\Pricing\Enums\PricingModel;
use Liberu\Billing\Pricing\Models\PricingPlan;

final class CalculatePricingPlanAmount
{
    /**
     * Calculate a plan's charge in the plan currency's minor units.
     *
     * Tier ceilings are cumulative. The final tier is unbounded, matching the
     * graduated pricing behavior of the legacy billing service.
     *
     * @param  array{quantity?: int|float}  $options
     */
    public function execute(PricingPlan $plan, array $options = []): int
    {
        $quantity = $options['quantity'] ?? 1;
        if (! is_int($quantity) && ! is_float($quantity) || $quantity < 0) {
            throw new \InvalidArgumentException('Pricing quantity must be zero or greater.');
        }

        if ($plan->pricing_model !== PricingModel::Usage && $plan->pricing_model !== PricingModel::Tiered) {
            return (int) $plan->unit_amount_minor;
        }

        if ($plan->pricing_model === PricingModel::Usage && $plan->tiers === []) {
            return (int) round($quantity * (int) $plan->unit_amount_minor);
        }

        $tiers = $plan->tiers;
        if (! is_array($tiers) || $tiers === []) {
            throw new \InvalidArgumentException('Tiered pricing requires tier definitions.');
        }

        $amount = 0;
        $remaining = $quantity;
        $previousCeiling = 0.0;
        foreach ($tiers as $tier) {
            if (! is_array($tier)) {
                throw new \InvalidArgumentException('Pricing tiers must be arrays.');
            }
            $rate = (int) ($tier['unit_amount_minor'] ?? $tier['rate'] ?? -1);
            $ceiling = $tier['up_to'] ?? $tier['max_usage'] ?? null;
            if ($rate < 0 || ($ceiling !== null && (! is_int($ceiling) && ! is_float($ceiling) || $ceiling < $previousCeiling))) {
                throw new \InvalidArgumentException('Pricing tier definitions are invalid.');
            }
            if ($ceiling !== null) {
                $previousCeiling = (float) $ceiling;
            }
        }

        $previousCeiling = 0.0;
        $lastTier = array_key_last($tiers);

        foreach ($tiers as $index => $tier) {
            $rate = (int) ($tier['unit_amount_minor'] ?? $tier['rate'] ?? -1);
            $ceiling = $tier['up_to'] ?? $tier['max_usage'] ?? null;
            if ($rate < 0 || ($ceiling !== null && (! is_int($ceiling) && ! is_float($ceiling) || $ceiling < $previousCeiling))) {
                throw new \InvalidArgumentException('Pricing tier definitions are invalid.');
            }

            $width = $index === $lastTier || $ceiling === null
                ? $remaining
                : max(0.0, (float) $ceiling - $previousCeiling);
            $tierQuantity = min($remaining, $width);
            $amount += (int) round($tierQuantity * $rate);
            $remaining -= $tierQuantity;
            if ($ceiling !== null) {
                $previousCeiling = (float) $ceiling;
            }
            if ($remaining <= 0) {
                break;
            }
        }

        return $amount;
    }
}
