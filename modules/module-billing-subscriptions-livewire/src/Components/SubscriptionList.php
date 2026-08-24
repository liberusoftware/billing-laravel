<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Livewire\Components;

use Illuminate\View\View;
use Liberu\Billing\Subscriptions\Actions\ActivateSubscription;
use Liberu\Billing\Subscriptions\Queries\ListSubscriptions;
use Livewire\Component;

final class SubscriptionList extends Component
{
    public int $pricingPlanId = 0;

    public int $trialDays = 0;

    public bool $showActivate = false;

    public function activate(ActivateSubscription $activate): void
    {
        $this->validate([
            'pricingPlanId' => ['nullable', 'integer', 'min:0'],
            'trialDays' => ['required', 'integer', 'min:0', 'max:365'],
        ]);
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $activate->execute([
            'team_id' => $teamId,
            'pricing_plan_id' => $this->pricingPlanId > 0 ? $this->pricingPlanId : null,
            'trial_days' => $this->trialDays,
        ]);
        $this->reset(['pricingPlanId', 'trialDays', 'showActivate']);
        session()->flash('module-billing-subscriptions-message', __('Subscription activated.'));
    }

    public function render(ListSubscriptions $query): View
    {
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return view('module-billing-subscriptions-livewire::subscription-list', [
            'subscriptions' => $query->execute($teamId === null ? null : (int) $teamId),
        ]);
    }
}
