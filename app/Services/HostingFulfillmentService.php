<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HostingFulfillment;
use App\Models\Invoice;
use App\Models\Order;

class HostingFulfillmentService
{
    public function prepare(Invoice $invoice): void
    {
        if ($invoice->status !== 'paid') {
            return;
        }
        foreach (Order::query()->where('invoice_id', $invoice->getKey())->where('customer_id', $invoice->customer_id)->get() as $order) {
            $subscription = $order->subscription;
            if ($subscription === null || $subscription->customer_id !== $invoice->customer_id
                || $subscription->productService?->type !== 'hosting') {
                continue;
            }
            HostingFulfillment::query()->firstOrCreate(['order_id' => $order->getKey()], [
                'invoice_id' => $invoice->getKey(), 'subscription_id' => $subscription->getKey(),
            ]);
        }
    }
}
