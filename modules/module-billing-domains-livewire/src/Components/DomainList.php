<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Domains\Actions\CreateDomain;
use Liberu\Billing\Domains\Models\Domain;
use Liberu\Billing\Domains\Queries\ListDomainsRecords;
use Livewire\Component;

final class DomainList extends Component
{
    public string $name = '';

    public string $registrar = '';

    public bool $showCreate = false;

    public function createDomain(CreateDomain $create): void
    {
        Gate::authorize('create', Domain::class);
        $this->validate(['name' => ['required', 'string', 'max:255'], 'registrar' => ['nullable', 'string', 'max:100']]);
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $create->handle((int) $teamId, ['name' => $this->name, 'registrar' => $this->registrar ?: null]);
        $this->reset(['name', 'registrar', 'showCreate']);
        session()->flash('module-billing-domains-message', __('Domain created.'));
    }

    public function render(ListDomainsRecords $query): View
    {
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return view('module-billing-domains-livewire::domain-list', ['domains' => $query->handle((int) $teamId)]);
    }
}
