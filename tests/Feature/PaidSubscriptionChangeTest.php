<?php

declare(strict_types=1);

use App\Models\Invoice;
use App\Models\Products_Service;
use App\Models\Subscription;
use App\Models\SubscriptionChange;
use App\Services\ServiceProvisioningService;

it('applies an upgrade once after the matching customer pays', function (): void {
    $subscription = Subscription::factory()->create();
    $target = Products_Service::factory()->create(['type' => $subscription->productService->type]);
    $invoice = Invoice::factory()->create(['customer_id' => $subscription->customer_id, 'subscription_id' => $subscription->id, 'status' => 'pending']);
    $change = SubscriptionChange::query()->create(['subscription_id' => $subscription->id, 'customer_id' => $subscription->customer_id, 'invoice_id' => $invoice->id, 'target_product_service_id' => $target->id, 'type' => 'upgrade', 'status' => 'pending']);
    $this->mock(ServiceProvisioningService::class)->shouldReceive('manageService')->once()->withArgs(fn ($model, $action, $options) => $model->id === $subscription->id && $action === 'upgrade' && $options['new_product']->id === $target->id)->andReturn(true);
    $this->artisan('subscriptions:process-changes')->assertSuccessful();
    expect($change->fresh()->status)->toBe('pending');
    $invoice->markAsPaid();
    $this->artisan('subscriptions:process-changes')->assertSuccessful();
    $this->artisan('subscriptions:process-changes')->assertSuccessful();
    expect($change->fresh()->status)->toBe('completed')->and($subscription->fresh()->product_service_id)->toBe($target->id);
});

it('does not apply a paid invoice to another customers subscription', function (): void {
    $subscription = Subscription::factory()->create();
    $target = Products_Service::factory()->create();
    $invoice = Invoice::factory()->create(['status' => 'paid']);
    $change = SubscriptionChange::query()->create(['subscription_id' => $subscription->id, 'customer_id' => $subscription->customer_id, 'invoice_id' => $invoice->id, 'target_product_service_id' => $target->id, 'type' => 'upgrade', 'status' => 'pending']);
    $this->mock(ServiceProvisioningService::class)->shouldNotReceive('manageService');
    $this->artisan('subscriptions:process-changes')->assertSuccessful();
    expect($change->fresh()->status)->toBe('pending');
});
