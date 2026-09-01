<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Subscriptions\Actions\ActivateSubscription;
use Liberu\Billing\Subscriptions\Actions\CancelSubscription;
use Liberu\Billing\Subscriptions\Actions\ChangeSubscriptionPlan;
use Liberu\Billing\Subscriptions\Actions\ExpireSubscriptions;
use Liberu\Billing\Subscriptions\Actions\PauseSubscription;
use Liberu\Billing\Subscriptions\Actions\RenewSubscription;
use Liberu\Billing\Subscriptions\Actions\ResumeSubscription;
use Liberu\Billing\Subscriptions\Enums\SubscriptionStatus;
use Liberu\Billing\Subscriptions\Models\Subscription;
use Liberu\Billing\Subscriptions\Queries\ListSubscriptions;
use Livewire\Component;

final class SubscriptionList extends Component
{
    public int $pricingPlanId = 0;

    public int $trialDays = 0;

    public int $periodDays = 30;

    public bool $showActivate = false;

    public int $filterCustomerId = 0;

    public string $filterStatus = '';

    public function activate(ActivateSubscription $activate): void
    {
        Gate::authorize('create', Subscription::class);
        $this->validate([
            'pricingPlanId' => ['nullable', 'integer', 'min:0'],
            'trialDays' => ['required', 'integer', 'min:0', 'max:365'],
            'periodDays' => ['required', 'integer', 'min:1', 'max:3660'],
        ]);
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $activate->execute([
            'team_id' => $teamId,
            'pricing_plan_id' => $this->pricingPlanId > 0 ? $this->pricingPlanId : null,
            'trial_days' => $this->trialDays,
            'period_days' => $this->periodDays,
        ]);
        $this->reset(['pricingPlanId', 'trialDays', 'periodDays', 'showActivate']);
        $this->periodDays = 30;
        session()->flash('module-billing-subscriptions-message', __('Subscription activated.'));
    }

    public function renew(int $subscriptionId, RenewSubscription $renew): void
    {
        $subscription = $this->authorizedSubscription($subscriptionId);
        $renew->execute($subscription);
        session()->flash('module-billing-subscriptions-message', __('Subscription renewed.'));
    }

    public function pause(int $subscriptionId, PauseSubscription $pause): void
    {
        $pause->execute($this->authorizedSubscription($subscriptionId));
        session()->flash('module-billing-subscriptions-message', __('Subscription paused.'));
    }

    public function resume(int $subscriptionId, ResumeSubscription $resume): void
    {
        $resume->execute($this->authorizedSubscription($subscriptionId));
        session()->flash('module-billing-subscriptions-message', __('Subscription resumed.'));
    }

    public function cancel(int $subscriptionId, CancelSubscription $cancel): void
    {
        $cancel->execute($this->authorizedSubscription($subscriptionId));
        session()->flash('module-billing-subscriptions-message', __('Subscription cancelled.'));
    }

    public function changePlan(int $subscriptionId, ChangeSubscriptionPlan $change): void
    {
        $this->validate(['pricingPlanId' => ['required', 'integer', 'min:1']]);
        $change->execute($this->authorizedSubscription($subscriptionId), $this->pricingPlanId);
        $this->reset('pricingPlanId');
        session()->flash('module-billing-subscriptions-message', __('Subscription plan changed.'));
    }

    public function expireDue(ExpireSubscriptions $expire): void
    {
        Gate::authorize('create', Subscription::class);
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $count = $expire->execute($teamId === null ? null : (int) $teamId);
        session()->flash('module-billing-subscriptions-message', trans_choice(':count subscription expired.|:count subscriptions expired.', $count, ['count' => $count]));
    }

    private function authorizedSubscription(int $subscriptionId): Subscription
    {
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $subscription = Subscription::query()->whereKey($subscriptionId)->where('team_id', $teamId)->firstOrFail();
        Gate::authorize('update', $subscription);

        return $subscription;
    }

    public function render(ListSubscriptions $query): View
    {
        Gate::authorize('viewAny', Subscription::class);
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return view('module-billing-subscriptions-livewire::subscription-list', [
            'subscriptions' => $query->execute($teamId === null ? null : (int) $teamId, 25, $this->filterCustomerId > 0 ? $this->filterCustomerId : null, $this->filterStatus !== '' ? SubscriptionStatus::tryFrom($this->filterStatus) : null),
        ]);
    }
}
