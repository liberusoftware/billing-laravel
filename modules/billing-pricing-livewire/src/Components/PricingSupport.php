<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Livewire\Components;

use Illuminate\View\View;
use Liberu\Billing\Pricing\Models\PricingDiscount;
use Liberu\Billing\Pricing\Models\PricingSnapshot;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class PricingSupport extends Component
{
    public function render(): View
    {
        Gate::authorize('viewAny', PricingSnapshot::class);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));

        return view('billing-pricing-livewire::support', ['discounts' => PricingDiscount::query()->where('team_id', $team)->latest()->get(), 'snapshots' => PricingSnapshot::query()->where('team_id', $team)->latest()->get()]);
    }
}
