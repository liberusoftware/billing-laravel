<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Communications\Actions\CreateCommunicationProvider;
use Liberu\Billing\Communications\Actions\ImportCommunicationUsage;
use Liberu\Billing\Communications\Models\CommunicationProvider;
use Liberu\Billing\Communications\Models\CommunicationUsageImport;
use Livewire\Component;

final class CommunicationProviders extends Component
{
    public string $name = '';

    public string $driver = '';

    public string $provider = '';

    public int $rows = 1;

    public int $totalAmountMinor = 0;

    public string $currency = 'USD';

    public function createProvider(CreateCommunicationProvider $create): void
    {
        Gate::authorize('create', CommunicationProvider::class);
        $this->validate(['name' => ['required', 'string', 'max:100'], 'driver' => ['required', 'string', 'max:100']]);
        $create->handle($this->teamId(), ['name' => $this->name, 'driver' => $this->driver]);
        $this->reset(['name', 'driver']);
        session()->flash('billing-communications-providers-message', __('Communication provider created.'));
    }

    public function importUsage(ImportCommunicationUsage $import): void
    {
        Gate::authorize('create', CommunicationUsageImport::class);
        $this->validate([
            'provider' => ['required', 'string', 'max:100'],
            'rows' => ['required', 'integer', 'min:1'],
            'totalAmountMinor' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
        ]);
        $import->handle($this->teamId(), ['provider' => $this->provider, 'rows' => $this->rows, 'total_amount_minor' => $this->totalAmountMinor, 'currency' => $this->currency]);
        $this->reset(['provider', 'rows', 'totalAmountMinor']);
        $this->rows = 1;
        session()->flash('billing-communications-providers-message', __('Communication usage imported.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', CommunicationProvider::class);
        $team = $this->teamId();

        return view('billing-communications-livewire::providers', [
            'providers' => CommunicationProvider::query()->where('team_id', $team)->latest()->get(),
            'usageImports' => CommunicationUsageImport::query()->where('team_id', $team)->latest()->get(),
        ]);
    }

    private function teamId(): int
    {
        return (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
    }
}
