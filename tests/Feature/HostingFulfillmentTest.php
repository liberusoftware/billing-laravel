<?php

declare(strict_types=1);

use App\Models\HostingFulfillment;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Products_Service;
use App\Models\Subscription;
use App\Services\HostingFulfillmentService;

it('queues hosting once only after the matching order is paid', function (): void {
    $product = Products_Service::factory()->create(['type' => 'hosting']);
    $subscription = Subscription::factory()->create(['product_service_id' => $product->id]);
    $invoice = Invoice::factory()->create(['subscription_id' => $subscription->id, 'customer_id' => $subscription->customer_id, 'status' => 'pending']);
    $order = Order::query()->create(['subscription_id' => $subscription->id, 'customer_id' => $subscription->customer_id, 'invoice_id' => $invoice->id]);
    $service = app(HostingFulfillmentService::class);
    $service->prepare($invoice);
    expect(HostingFulfillment::query()->count())->toBe(0);
    $invoice->markAsPaid();
    $service->prepare($invoice);
    $service->prepare($invoice);
    expect(HostingFulfillment::query()->where('order_id', $order->id)->count())->toBe(1);
    $this->assertDatabaseHas('hosting_fulfillments', ['order_id' => $order->id, 'status' => 'pending']);
});
