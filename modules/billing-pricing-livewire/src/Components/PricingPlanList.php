<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Livewire\Components;

use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Pricing\Actions\CreatePricingPlan;
use Liberu\Billing\Pricing\Models\PricingPlan;
use Liberu\Billing\Pricing\Queries\ListPricingPlans;
use Livewire\Component;

final class PricingPlanList extends Component
{
    public string $name = '';

    public string $pricingModel = 'recurring';

    public string $currency = 'USD';

    public int $unitAmountMinor = 0;

    public bool $showCreate = false;

    public function save(CreatePricingPlan $create): void
    {
        Gate::authorize('create', PricingPlan::class);
        $this->validate(['name' => ['required', 'string', 'max:255'], 'pricingModel' => ['required', 'in:recurring,one_time,usage,tiered'], 'currency' => ['required', 'string', 'size:3', 'alpha'], 'unitAmountMinor' => ['required', 'integer', 'min:0']]);
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $create->execute(['name' => $this->name, 'pricing_model' => $this->pricingModel, 'currency' => $this->currency, 'unit_amount_minor' => $this->unitAmountMinor, 'team_id' => $teamId]);
        $this->reset(['name', 'unitAmountMinor']);
        $this->showCreate = false;
        session()->flash('billing-pricing-message', __('Pricing plan created.'));
    }

    public function render(ListPricingPlans $query): View
    {
        Gate::authorize('viewAny', PricingPlan::class);
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return view('billing-pricing-livewire::plan-list', ['plans' => $query->execute($teamId === null ? null : (int) $teamId)]);
    }
}
