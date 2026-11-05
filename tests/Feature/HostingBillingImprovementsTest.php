<?php

declare(strict_types=1);

use App\Filament\Client\Pages\OrderForm;
use App\Models\Customer;
use App\Models\OrderFormTemplate;
use App\Models\Products_Service;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\ProjectBillingService;
use App\Services\ServiceProvisioningService;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('never invoices time from another project or a running timer', function (): void {
    $project = Project::factory()->create();
    $valid = TimeEntry::factory()->create(['task_id' => Task::factory()->create(['project_id' => $project->id])->id, 'duration_seconds' => 60, 'rate' => 100]);
    $foreign = TimeEntry::factory()->create();
    $running = TimeEntry::factory()->running()->create(['task_id' => $valid->task_id]);

    $invoice = app(ProjectBillingService::class)->invoiceTime($project, [$valid->id, $foreign->id, $running->id]);

    expect($invoice->items)->toHaveCount(1)
        ->and((float) $invoice->total_amount)->toBe(1.67)
        ->and($foreign->fresh()->invoiced_at)->toBeNull()
        ->and($running->fresh()->invoiced_at)->toBeNull();
});

it('publishes only active plans offered by active order forms', function (): void {
    $this->withoutVite();
    $visible = SubscriptionPlan::create(['name' => 'Public hosting plan', 'code' => 'public', 'price' => 12, 'currency' => 'GBP', 'is_active' => true]);
    $hidden = SubscriptionPlan::create(['name' => 'Private hosting plan', 'code' => 'private', 'price' => 20, 'currency' => 'GBP', 'is_active' => false]);
    $unlisted = SubscriptionPlan::create(['name' => 'Unlisted hosting plan', 'code' => 'unlisted', 'price' => 30, 'currency' => 'GBP', 'is_active' => true]);
    OrderFormTemplate::factory()->offering([$visible->id, $hidden->id])->create(['slug' => 'hosting']);

    $this->get('/')->assertSuccessful()->assertSee('Public hosting plan')->assertSee('GBP')
        ->assertSee('template=hosting')->assertDontSee($hidden->name)->assertDontSee($unlisted->name);
});

it('renders an honest empty storefront without configured offers', function (): void {
    $this->withoutVite();
    $this->get('/')->assertSuccessful()->assertSee('preparing our service catalogue');
});

it('rejects a cheaper plan submitted as an upgrade without billing the customer', function (): void {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['user_id' => $user->id]);
    $current = Products_Service::factory()->create(['base_price' => 50, 'type' => 'hosting']);
    $target = Products_Service::factory()->create(['base_price' => 10, 'type' => 'hosting']);
    $subscription = Subscription::factory()->create(['customer_id' => $customer->id, 'product_service_id' => $current->id, 'status' => 'active', 'start_date' => now()->subDays(15), 'end_date' => now()->addDays(15)]);

    $this->actingAs($user)->post(route('client.services.upgrade', $subscription), ['new_service_id' => $target->id])->assertUnprocessable();
    expect($subscription->invoices()->count())->toBe(0);
});

it('creates one balanced upgrade invoice and rejects duplicate requests', function (): void {
    $this->travelTo(now()->startOfDay());
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['user_id' => $user->id]);
    $current = Products_Service::factory()->create(['base_price' => 10, 'type' => 'hosting']);
    $target = Products_Service::factory()->create(['base_price' => 20, 'type' => 'hosting']);
    $subscription = Subscription::factory()->create(['customer_id' => $customer->id, 'product_service_id' => $current->id, 'status' => 'active', 'renewal_period' => 'monthly', 'start_date' => now()->subDays(15), 'end_date' => now()->addDays(15)]);

    $this->actingAs($user)->post(route('client.services.upgrade', $subscription), ['new_service_id' => $target->id])->assertRedirect();
    $invoice = $subscription->invoices()->firstOrFail();
    expect((float) $invoice->total_amount)->toBe(5.0)
        ->and((float) $invoice->items->sum('total_price'))->toBe(5.0)
        ->and($subscription->fresh()->product_service_id)->toBe($current->id);
    $this->post(route('client.services.upgrade', $subscription), ['new_service_id' => $target->id])->assertUnprocessable();
    expect($subscription->invoices()->count())->toBe(1);
});

it('applies due downgrades once and updates the recurring term price', function (): void {
    $current = Products_Service::factory()->create(['base_price' => 30, 'type' => 'hosting']);
    $target = Products_Service::factory()->create(['base_price' => 10, 'type' => 'hosting']);
    $subscription = Subscription::factory()->create(['product_service_id' => $current->id, 'status' => 'active', 'renewal_period' => 'annually', 'end_date' => now()->subMinute(), 'scheduled_change' => ['type' => 'downgrade', 'product_service_id' => $target->id, 'effective_date' => now()->subMinute()->toIso8601String()]]);
    $this->mock(ServiceProvisioningService::class)->shouldReceive('manageService')->once()->withArgs(fn ($model, $action, $options) => $model->id === $subscription->id && $action === 'downgrade' && $options['new_product']->id === $target->id)->andReturn(true);

    $this->artisan('subscriptions:process-changes')->assertSuccessful();
    $this->artisan('subscriptions:process-changes')->assertSuccessful();
    expect($subscription->fresh()->scheduled_change)->toBeNull()
        ->and($subscription->fresh()->product_service_id)->toBe($target->id)
        ->and($subscription->fresh()->price)->toBe('120.00');
});

it('keeps a scheduled cancellation when the provider rejects it', function (): void {
    $subscription = Subscription::factory()->create(['status' => 'active', 'end_date' => now()->subMinute(), 'scheduled_change' => ['type' => 'cancel', 'effective_date' => now()->subMinute()->toIso8601String()]]);
    $this->mock(ServiceProvisioningService::class)->shouldReceive('manageService')->once()->andReturn(false);

    $this->artisan('subscriptions:process-changes')->assertSuccessful();
    expect($subscription->fresh()->status)->toBe('active')->and($subscription->fresh()->scheduled_change['type'])->toBe('cancel');
});

it('cancels a due subscription once after successful provider termination', function (): void {
    $subscription = Subscription::factory()->create(['status' => 'active', 'auto_renew' => true, 'end_date' => now()->subMinute(), 'scheduled_change' => ['type' => 'cancel', 'effective_date' => now()->subMinute()->toIso8601String()]]);
    $this->mock(ServiceProvisioningService::class)->shouldReceive('manageService')->once()->withArgs(fn ($model, $action) => $model->id === $subscription->id && $action === 'terminate')->andReturn(true);

    $this->artisan('subscriptions:process-changes')->assertSuccessful();
    $this->artisan('subscriptions:process-changes')->assertSuccessful();
    expect($subscription->fresh()->status)->toBe('cancelled')
        ->and($subscription->fresh()->auto_renew)->toBeFalse()
        ->and($subscription->fresh()->scheduled_change)->toBeNull();
});

it('does not renew past a scheduled subscription change', function (): void {
    $subscription = Subscription::factory()->create(['status' => 'active', 'auto_renew' => true, 'end_date' => now()->addDays(10), 'scheduled_change' => ['type' => 'cancel', 'effective_date' => now()->addDays(10)->toIso8601String()]]);
    $previousEnd = $subscription->end_date->toIso8601String();
    expect($subscription->renew())->toBeFalse()->and($subscription->fresh()->end_date->toIso8601String())->toBe($previousEnd);
    $this->mock(ServiceProvisioningService::class)->shouldNotReceive('manageService');
    $this->artisan('subscriptions:process-changes')->assertSuccessful();
});

it('carries the public plan and template choice into checkout', function (): void {
    $user = User::factory()->create();
    $plan = SubscriptionPlan::create(['name' => 'Chosen plan', 'code' => 'chosen', 'price' => 15, 'currency' => 'USD', 'is_active' => true]);
    $template = OrderFormTemplate::factory()->offering([$plan->id])->create();
    $this->actingAs($user);
    $panel = Filament::getPanel('client');
    Filament::setCurrentPanel($panel);
    $panel->boot();

    Livewire::withQueryParams(['template' => $template->slug, 'plan' => (string) $plan->id])
        ->test(OrderForm::class)
        ->assertSet('selectedPlan', $plan->id)
        ->assertSet('template.id', $template->id)
        ->assertSee('Order summary');
});

it('starts the renewed billing period at the previous expiry', function (): void {
    $subscription = Subscription::factory()->create(['status' => 'active', 'auto_renew' => true, 'renewal_period' => 'monthly', 'start_date' => now()->subMonth(), 'end_date' => now(), 'scheduled_change' => null]);
    $previousEnd = $subscription->end_date->toIso8601String();
    expect($subscription->renew())->toBeTrue()->and($subscription->fresh()->start_date->toIso8601String())->toBe($previousEnd);
});
