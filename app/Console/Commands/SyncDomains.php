<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\Registrars\EnomClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Description('Synchronize per-domain expiration dates from registrars')]
#[Signature('domains:sync')]
class SyncDomains extends Command
{
    public function handle(EnomClient $enom): void
    {
        $subscriptions = Subscription::query()
            ->where('domain_registrar', 'enom')
            ->whereNotNull('domain_name')
            ->get();

        foreach ($subscriptions as $subscription) {
            try {
                $expiration = $enom->getDomainExpiration($subscription->domain_name);
                if ($expiration !== null) {
                    $subscription->update(['domain_expiration_date' => $expiration]);
                }
            } catch (Throwable $e) {
                $this->error("Failed to sync {$subscription->domain_name}: {$e->getMessage()}");
            }
        }

        $this->info('Domain synchronization complete.');
    }
}
