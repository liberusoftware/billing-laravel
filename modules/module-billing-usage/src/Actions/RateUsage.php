<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Actions;

use Liberu\Billing\Usage\Models\Meter;

final class RateUsage
{
    public function execute(Meter $meter, float $quantity): int
    {
        if ($quantity < 0 || ! $meter->active) {
            throw new \InvalidArgumentException('Usage rating inputs are invalid.');
        }

        $tiers = $meter->metadata['tiers'] ?? [];
        if ($tiers === []) {
            return (int) round($quantity * $meter->unit_price_minor);
        }

        foreach ($tiers as $tier) {
            if (! is_array($tier) || ! isset($tier['up_to']) || (float) $tier['up_to'] <= 0 || ! isset($tier['unit_price_minor']) || (int) $tier['unit_price_minor'] < 0) {
                throw new \InvalidArgumentException('Usage rating tiers are invalid.');
            }
        }

        usort($tiers, fn (array $a, array $b): int => ($a['up_to'] ?? PHP_INT_MAX) <=> ($b['up_to'] ?? PHP_INT_MAX));
        $remaining = $quantity;
        $previous = 0.0;
        $total = 0;
        foreach ($tiers as $tier) {
            $limit = (float) ($tier['up_to'] ?? PHP_INT_MAX);
            $units = min($remaining, max(0, $limit - $previous));
            $total += (int) round($units * (int) ($tier['unit_price_minor'] ?? $meter->unit_price_minor));
            $remaining -= $units;
            $previous = $limit;
            if ($remaining <= 0) {
                break;
            }
        }

        return $total + ($remaining > 0 ? (int) round($remaining * $meter->unit_price_minor) : 0);
    }
}
