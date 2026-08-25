<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Actions;

final class CalculateProration
{
    public function execute(int $amountMinor, int $remainingDays, int $periodDays): int
    {
        if ($amountMinor < 0 || $remainingDays < 0 || $periodDays < 1 || $remainingDays > $periodDays) {
            throw new \InvalidArgumentException('Proration inputs are invalid.');
        }

        return (int) round($amountMinor * $remainingDays / $periodDays);
    }
}
