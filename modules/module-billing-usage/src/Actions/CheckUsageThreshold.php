<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Actions;

use Liberu\Billing\Usage\Models\Meter;

final class CheckUsageThreshold
{
    public function execute(Meter $meter, float $quantity): bool
    {
        return $meter->threshold !== null && $quantity >= (float) $meter->threshold;
    }
}
