<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\InvoiceStatusChanged;
use App\Models\DomainRenewal;
use App\Models\Invoice;

class RenewDomainOnPayment
{
    public function handle(InvoiceStatusChanged $event): void
    {
        if ($event->status !== 'paid') {
            return;
        }
        $invoice = Invoice::find($event->invoice->id);
        $subscription = $invoice?->subscription;
        if ($invoice?->status !== 'paid' || $subscription === null
            || $subscription->customer_id !== $invoice->customer_id
            || ! $subscription->domain_name || ! $subscription->domain_registrar) {
            return;
        }

        // The scheduled processor can only see this request after the payment commits.
        DomainRenewal::firstOrCreate(['invoice_id' => $invoice->id], [
            'subscription_id' => $subscription->id,
            'domain' => $subscription->domain_name,
            'registrar' => $subscription->domain_registrar,
        ]);
    }
}
