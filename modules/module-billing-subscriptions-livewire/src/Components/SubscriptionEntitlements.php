<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Livewire\Components;

use Illuminate\View\View;
use Liberu\Billing\Subscriptions\Models\Subscription;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class SubscriptionEntitlements extends Component
{
    public function render(): View
    {
        Gate::authorize('viewAny', Subscription::class);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));

        return view('module-billing-subscriptions-livewire::entitlements', ['subscriptions' => Subscription::query()->where('team_id', $team)->latest()->get()]);
    }
}
