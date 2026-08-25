<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Usage\Actions\CorrectUsage;
use Liberu\Billing\Usage\Actions\CheckUsageThreshold;
use Liberu\Billing\Usage\Actions\RateUsage;
use Liberu\Billing\Usage\Models\Meter;
use Liberu\Billing\Usage\Models\UsageRecord;
use Livewire\Component;

final class UsageRecords extends Component
{
    public ?int $selectedRecordId = null;

    public float $correctionQuantity = 0.0;

    public string $correctionEventKey = '';

    public ?int $selectedMeterId = null;

    public float $ratingQuantity = 0.0;

    public function rate(RateUsage $rate, CheckUsageThreshold $threshold): void
    {
        $this->validate(['selectedMeterId' => ['required', 'integer', 'min:1'], 'ratingQuantity' => ['required', 'numeric', 'min:0']]);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
        $meter = Meter::query()->where(function ($query) use ($team): void {
            $query->whereNull('team_id')->orWhere('team_id', $team);
        })->findOrFail($this->selectedMeterId);
        Gate::authorize('view', $meter);
        $amount = $rate->execute($meter, $this->ratingQuantity);
        $reached = $threshold->execute($meter, $this->ratingQuantity);
        $this->reset(['selectedMeterId', 'ratingQuantity']);
        session()->flash('module-billing-usage-message', __('Usage rated at :amount minor units; threshold: :threshold.', ['amount' => $amount, 'threshold' => $reached ? __('reached') : __('not reached')]));
    }

    public function correct(CorrectUsage $correct): void
    {
        Gate::authorize('create', UsageRecord::class);
        $this->validate(['selectedRecordId' => ['required', 'integer', 'min:1'], 'correctionQuantity' => ['required', 'numeric', 'not_in:0'], 'correctionEventKey' => ['required', 'string', 'max:255']]);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
        $record = UsageRecord::query()->where('team_id', $team)->findOrFail($this->selectedRecordId);
        $correct->execute($record, $this->correctionQuantity, $this->correctionEventKey);
        $this->reset(['selectedRecordId', 'correctionQuantity', 'correctionEventKey']);
        session()->flash('module-billing-usage-message', __('Usage correction recorded.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', UsageRecord::class);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));

        return view('module-billing-usage-livewire::records', ['meters' => Meter::query()->where(function ($query) use ($team): void {
            $query->whereNull('team_id')->orWhere('team_id', $team);
        })->latest()->get(), 'records' => UsageRecord::query()->where('team_id', $team)->latest('occurred_at')->get()]);
    }
}
