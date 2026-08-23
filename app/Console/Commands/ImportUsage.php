<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\UsageImportService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

#[Description('Import metered usage for active usage-based subscriptions')]
#[Signature('usage:import')]
class ImportUsage extends Command
{
    public function handle(UsageImportService $service): int
    {
        $subscriptions = Subscription::query()
            ->where('status', 'active')
            ->whereHas('productService', function (Builder $query): void {
                $query->where('pricing_model', 'usage_based');
            })
            ->with('productService')
            ->get();

        foreach ($subscriptions as $subscription) {
            foreach ($subscription->productService->getUsageMetrics() as $metric) {
                // ponytail: pull real usage from the control panel here; stubbed to 0 for the slice.
                $service->importUsage($subscription, $metric, 0.0);
            }
        }

        $this->info("Imported usage for {$subscriptions->count()} metered subscription(s).");

        return Command::SUCCESS;
    }
}
