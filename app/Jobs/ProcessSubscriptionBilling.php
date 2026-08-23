<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessSubscriptionBilling implements ShouldQueue
{
    use Queueable;

    public function handle(BillingService $billingService): void
    {
        Subscription::query()
            ->where(
                'status',
                'active'
            )
            ->where(
                'auto_renew',
                true
            )
            ->get()
            ->each(
                function ($subscription) use ($billingService): void {
                    if ($subscription->needsBilling()) {
                        $invoice = $billingService->generateInvoice($subscription);
                        $billingService->processAutomaticPayment($invoice);
                        $subscription->renew();
                    }
                }
            );
    }
}
