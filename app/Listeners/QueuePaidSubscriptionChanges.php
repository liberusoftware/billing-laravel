<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\InvoiceStatusChanged;
use App\Models\SubscriptionChange;

class QueuePaidSubscriptionChanges
{
    public function handle(InvoiceStatusChanged $event): void
    {
        if ($event->status !== 'paid' || $event->invoice->fresh()?->status !== 'paid') {
            return;
        }
        SubscriptionChange::query()->where('invoice_id', $event->invoice->getKey())
            ->where('customer_id', $event->invoice->customer_id)->where('status', 'pending')
            ->update(['status' => 'queued']);
    }
}
