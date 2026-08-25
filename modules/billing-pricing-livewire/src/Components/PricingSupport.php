<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Pricing\Actions\CapturePricingSnapshot;
use Liberu\Billing\Pricing\Actions\RedeemPricingDiscount;
use Liberu\Billing\Pricing\Models\PricingDiscount;
use Liberu\Billing\Pricing\Models\PricingPlan;
use Liberu\Billing\Pricing\Models\PricingSnapshot;
use Livewire\Component;

final class PricingSupport extends Component
{
    public ?int $selectedPlanId = null;

    public ?int $selectedDiscountId = null;

    public function captureSnapshot(CapturePricingSnapshot $capture): void
    {
        $plan = PricingPlan::query()->whereKey($this->selectedPlanId)->where('team_id', $this->team())->firstOrFail();
        Gate::authorize('update', $plan);
        $capture->execute($plan);
        session()->flash('billing-pricing-support-message', __('Pricing snapshot captured.'));
    }

    public function redeemDiscount(RedeemPricingDiscount $redeem): void
    {
        $discount = PricingDiscount::query()->whereKey($this->selectedDiscountId)->where('team_id', $this->team())->firstOrFail();
        Gate::authorize('update', $discount);
        $redeem->execute($discount);
        session()->flash('billing-pricing-support-message', __('Pricing discount redeemed.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', PricingSnapshot::class);
        $team = $this->team();

        return view('billing-pricing-livewire::support', ['discounts' => PricingDiscount::query()->where('team_id', $team)->latest()->get(), 'snapshots' => PricingSnapshot::query()->where('team_id', $team)->latest()->get(), 'plans' => PricingPlan::query()->where('team_id', $team)->latest()->get()]);
    }

    private function team(): int
    {
        return (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
    }
}
