<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Usage\Actions\DefineMeter;
use Liberu\Billing\Usage\Actions\IngestUsage;
use Liberu\Billing\Usage\Models\Meter;
use Liberu\Billing\Usage\Queries\ListMeters;
use Livewire\Component;

final class MeterList extends Component
{
    public string $name = '';

    public string $code = '';

    public string $unit = 'unit';

    public int $unitPriceMinor = 0;

    public string $currency = 'USD';

    public string $eventKey = '';

    public float $quantity = 0.0;

    public ?int $selectedMeterId = null;

    public bool $showCreate = false;

    public function createMeter(DefineMeter $define): void
    {
        Gate::authorize('create', Meter::class);
        $this->validate(['name' => ['nullable', 'string', 'max:255'], 'code' => ['required', 'string', 'max:100'], 'unit' => ['required', 'string', 'max:50'], 'unitPriceMinor' => ['required', 'integer', 'min:0'], 'currency' => ['required', 'string', 'size:3', 'alpha']]);
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $define->execute(['team_id' => $teamId, 'name' => $this->name, 'code' => $this->code, 'unit' => $this->unit, 'unit_price_minor' => $this->unitPriceMinor, 'currency' => $this->currency]);
        $this->reset(['name', 'code', 'unitPriceMinor', 'showCreate']);
        session()->flash('module-billing-usage-message', __('Usage meter created.'));
    }

    public function ingest(IngestUsage $ingest): void
    {
        Gate::authorize('create', Meter::class);
        $this->validate(['selectedMeterId' => ['required', 'integer', 'min:1'], 'eventKey' => ['required', 'string', 'max:255'], 'quantity' => ['required', 'numeric', 'gt:0']]);
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $meter = Meter::query()->whereKey($this->selectedMeterId)->when($teamId !== null, fn ($query) => $query->where(fn ($query) => $query->whereNull('team_id')->orWhere('team_id', (int) $teamId)))->firstOrFail();
        Gate::authorize('update', $meter);
        $ingest->execute($meter, ['event_key' => $this->eventKey, 'quantity' => $this->quantity]);
        $this->reset(['eventKey', 'quantity']);
        session()->flash('module-billing-usage-message', __('Usage recorded.'));
    }

    public function render(ListMeters $query): View
    {
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return view('module-billing-usage-livewire::meter-list', ['meters' => $query->execute($teamId === null ? null : (int) $teamId)]);
    }
}
