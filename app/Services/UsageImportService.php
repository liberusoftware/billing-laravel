<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Subscription;
use App\Models\UsageRecord;
use InvalidArgumentException;

class UsageImportService
{
    /**
     * Record metered usage for a subscription. Accumulates into the open
     * (unprocessed) record for this subscription+metric so a billing period
     * keeps a single running total until generateInvoice() flips it processed.
     *
     * The real usage figure (disk, bandwidth, ...) is pulled from the hosting
     * control panel; here it is passed in so the source stays swappable.
     */
    public function importUsage(Subscription $subscription, string $metric, float $quantity): UsageRecord
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Usage quantity cannot be negative.');
        }

        // ponytail: pull real usage from control panel here; caller passes the number for now.
        $record = UsageRecord::firstOrNew([
            'subscription_id' => $subscription->id,
            'metric_name' => $metric,
            'processed' => false,
        ]);

        $record->quantity = (string) ((float) $record->quantity + $quantity);
        $record->recorded_at = now();
        $record->save();

        return $record;
    }
}
