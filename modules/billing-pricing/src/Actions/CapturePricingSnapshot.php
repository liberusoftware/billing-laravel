<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Actions;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\QueryException;
use Liberu\Billing\Pricing\Events\PricingSnapshotCaptured;
use Liberu\Billing\Pricing\Models\PricingPlan;
use Liberu\Billing\Pricing\Models\PricingSnapshot;

final readonly class CapturePricingSnapshot
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(PricingPlan $plan): PricingSnapshot
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return $this->database->transaction(function () use ($plan): PricingSnapshot {
                    $version = ((int) PricingSnapshot::query()->where('pricing_plan_id', $plan->getKey())->lockForUpdate()->max('version')) + 1;

                    $snapshot = PricingSnapshot::query()->create(['team_id' => $plan->team_id, 'pricing_plan_id' => $plan->getKey(), 'version' => $version, 'payload' => $plan->toArray(), 'captured_at' => now()]);
                    PricingSnapshotCaptured::dispatch($snapshot);

                    return $snapshot;
                });
            } catch (QueryException $exception) {
                if ($attempt === 2 || ! str_contains(strtolower($exception->getMessage()), 'unique')) {
                    throw $exception;
                }
            }
        }

        throw new \LogicException('Unable to capture a pricing snapshot.');
    }
}
