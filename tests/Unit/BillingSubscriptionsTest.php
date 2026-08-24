<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Billing\Subscriptions\Actions\ActivateSubscription;
use Liberu\Billing\Subscriptions\Actions\CancelSubscription;
use Liberu\Billing\Subscriptions\Actions\ChangeSubscriptionPlan;
use Liberu\Billing\Subscriptions\Actions\PauseSubscription;
use Liberu\Billing\Subscriptions\Actions\RenewSubscription;
use Liberu\Billing\Subscriptions\Enums\SubscriptionStatus;

uses(RefreshDatabase::class);

it('activates a trial subscription with an entitlement', function () {
    $subscription = app(ActivateSubscription::class)->execute([
        'team_id' => 10,
        'pricing_plan_id' => 2,
        'trial_days' => 14,
    ]);

    expect($subscription->status)->toBe(SubscriptionStatus::Trialing)
        ->and($subscription->trial_ends_at)->not->toBeNull()
        ->and($subscription->entitlement_state['active'])->toBeTrue();
});

it('supports plan changes, pause, renewal, and cancellation', function () {
    $subscription = app(ActivateSubscription::class)->execute(['team_id' => 10]);
    $subscription = app(ChangeSubscriptionPlan::class)->execute($subscription, 4);
    $subscription = app(PauseSubscription::class)->execute($subscription);
    expect($subscription->status)->toBe(SubscriptionStatus::Paused);

    $subscription = app(RenewSubscription::class)->execute($subscription, 30);
    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->current_period_ends_at)->not->toBeNull();

    $subscription = app(CancelSubscription::class)->execute($subscription);
    expect($subscription->status)->toBe(SubscriptionStatus::Cancelled)
        ->and($subscription->auto_renew)->toBeFalse();
});

it('requires an owner and rejects terminal lifecycle mutations', function () {
    expect(fn () => app(ActivateSubscription::class)->execute([]))
        ->toThrow(InvalidArgumentException::class);

    $subscription = app(ActivateSubscription::class)->execute(['team_id' => 10]);
    $subscription = app(CancelSubscription::class)->execute($subscription);

    expect(fn () => app(PauseSubscription::class)->execute($subscription))
        ->toThrow(LogicException::class);
});
