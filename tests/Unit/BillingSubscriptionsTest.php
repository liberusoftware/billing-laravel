<?php

use App\Models\Customer;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Liberu\Billing\Subscriptions\Actions\ActivateSubscription;
use Liberu\Billing\Subscriptions\Actions\CancelSubscription;
use Liberu\Billing\Subscriptions\Actions\ChangeSubscriptionPlan;
use Liberu\Billing\Subscriptions\Actions\PauseSubscription;
use Liberu\Billing\Subscriptions\Actions\RenewSubscription;
use Liberu\Billing\Subscriptions\Actions\ResumeSubscription;
use Liberu\Billing\Subscriptions\Actions\UpdateEntitlementState;
use Liberu\Billing\Subscriptions\Enums\SubscriptionStatus;
use Liberu\Billing\Subscriptions\Events\SubscriptionEntitlementsUpdated;
use Liberu\Billing\Subscriptions\Events\SubscriptionPaused;
use Liberu\Billing\Subscriptions\Events\SubscriptionPlanChanged;
use Liberu\Billing\Subscriptions\Models\Subscription;

uses(RefreshDatabase::class);

it('activates a trial subscription with an entitlement', function () {
    DB::table('billing_pricing_plans')->insert([
        'id' => 2,
        'team_id' => 10,
        'name' => 'Trial plan',
        'pricing_model' => 'flat',
        'currency' => 'USD',
        'unit_amount_minor' => 1000,
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $subscription = app(ActivateSubscription::class)->execute([
        'team_id' => 10,
        'pricing_plan_id' => 2,
        'trial_days' => 14,
    ]);

    expect($subscription->status)->toBe(SubscriptionStatus::Trialing)
        ->and($subscription->trial_ends_at)->not->toBeNull()
        ->and($subscription->entitlement_state['active'])->toBeTrue();
});

it('carries legacy domain protection into the subscription boundary', function () {
    $subscription = app(ActivateSubscription::class)->execute([
        'team_id' => 10,
        'id_protection' => true,
    ]);

    expect($subscription->id_protection)->toBeTrue();
});

it('supports plan changes, pause, renewal, and cancellation', function () {
    Event::fake([SubscriptionPaused::class, SubscriptionPlanChanged::class]);
    DB::table('billing_pricing_plans')->insert([
        'id' => 4,
        'team_id' => 10,
        'name' => 'Changed plan',
        'pricing_model' => 'flat',
        'currency' => 'USD',
        'unit_amount_minor' => 1000,
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $subscription = app(ActivateSubscription::class)->execute(['team_id' => 10]);
    $subscription = app(ChangeSubscriptionPlan::class)->execute($subscription, 4);
    $subscription = app(PauseSubscription::class)->execute($subscription);
    expect($subscription->status)->toBe(SubscriptionStatus::Paused);
    Event::assertDispatched(SubscriptionPlanChanged::class);
    Event::assertDispatched(SubscriptionPaused::class);

    $subscription = app(RenewSubscription::class)->execute($subscription, 30);
    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->current_period_ends_at)->not->toBeNull();

    $subscription = app(CancelSubscription::class)->execute($subscription);
    expect($subscription->status)->toBe(SubscriptionStatus::Cancelled)
        ->and($subscription->auto_renew)->toBeFalse();
});

it('resumes only paused subscriptions and restores entitlements', function () {
    $subscription = app(ActivateSubscription::class)->execute(['team_id' => 10]);
    $subscription = app(PauseSubscription::class)->execute($subscription);
    $subscription = app(ResumeSubscription::class)->execute($subscription);

    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->paused_at)->toBeNull()
        ->and($subscription->entitlement_state['active'])->toBeTrue();

    expect(fn () => app(ResumeSubscription::class)->execute($subscription))
        ->toThrow(LogicException::class);
});

it('emits an event when entitlements change', function () {
    Event::fake([SubscriptionEntitlementsUpdated::class]);
    $subscription = app(ActivateSubscription::class)->execute(['team_id' => 10]);

    app(UpdateEntitlementState::class)->execute($subscription, ['active' => true, 'seats' => 5]);

    Event::assertDispatched(SubscriptionEntitlementsUpdated::class);
});

it('requires an owner and rejects terminal lifecycle mutations', function () {
    expect(fn () => app(ActivateSubscription::class)->execute([]))
        ->toThrow(InvalidArgumentException::class);

    $subscription = app(ActivateSubscription::class)->execute(['team_id' => 10]);
    $subscription = app(CancelSubscription::class)->execute($subscription);

    expect(fn () => app(PauseSubscription::class)->execute($subscription))
        ->toThrow(LogicException::class);
});

it('rechecks the locked subscription before renewing stale worker state', function () {
    $subscription = app(ActivateSubscription::class)->execute(['team_id' => 10]);
    $stale = Subscription::query()->findOrFail($subscription->getKey());

    app(CancelSubscription::class)->execute($subscription);

    expect(fn () => app(RenewSubscription::class)->execute($stale))
        ->toThrow(LogicException::class, 'Subscription cannot be renewed.')
        ->and($subscription->refresh()->status)->toBe(SubscriptionStatus::Cancelled);
});

it('does not change the plan after a subscription becomes terminal', function (): void {
    DB::table('billing_pricing_plans')->insert([
        'id' => 1,
        'team_id' => 10,
        'name' => 'Current plan',
        'pricing_model' => 'flat',
        'currency' => 'USD',
        'unit_amount_minor' => 1000,
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $subscription = app(ActivateSubscription::class)->execute(['team_id' => 10, 'pricing_plan_id' => 1]);
    $subscription->refresh();
    Subscription::query()->whereKey($subscription->getKey())->update(['status' => SubscriptionStatus::Cancelled->value]);

    expect(fn () => app(ChangeSubscriptionPlan::class)->execute($subscription, 2))
        ->toThrow(LogicException::class, 'A terminal subscription cannot change plans.');
});

it('does not pause a subscription after its persisted state becomes terminal', function (): void {
    $subscription = app(ActivateSubscription::class)->execute(['team_id' => 10]);
    $subscription->refresh();
    Subscription::query()->whereKey($subscription->getKey())->update(['status' => SubscriptionStatus::Cancelled->value]);

    expect(fn () => app(PauseSubscription::class)->execute($subscription))
        ->toThrow(LogicException::class, 'A terminal subscription cannot be paused.');
});

it('rejects a pricing plan owned by another team', function (): void {
    if (! Schema::hasTable('billing_pricing_plans')) {
        $this->markTestSkipped('Pricing tables are not installed.');
    }

    $planId = DB::table('billing_pricing_plans')->insertGetId([
        'team_id' => 20,
        'name' => 'Foreign plan',
        'pricing_model' => 'flat',
        'currency' => 'USD',
        'unit_amount_minor' => 1000,
        'status' => 'active',
        'metadata' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => app(ActivateSubscription::class)->execute([
        'team_id' => 10,
        'pricing_plan_id' => $planId,
    ]))->toThrow(InvalidArgumentException::class, 'Subscription pricing plan reference is invalid.');

    $subscription = app(ActivateSubscription::class)->execute(['team_id' => 10]);

    expect(fn () => app(ChangeSubscriptionPlan::class)->execute($subscription, $planId))
        ->toThrow(InvalidArgumentException::class, 'Subscription pricing plan reference is invalid.');
});

it('rejects a subscription customer owned by another team', function (): void {
    $team = Team::factory()->create(['id' => 20]);
    $customerId = Customer::factory()->create(['team_id' => $team->getKey()])->getKey();

    expect(fn () => app(ActivateSubscription::class)->execute([
        'team_id' => 10, 'customer_id' => $customerId,
    ]))->toThrow(InvalidArgumentException::class, 'Customer reference is invalid.');
});
