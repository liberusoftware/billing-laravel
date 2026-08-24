<?php

declare(strict_types=1);

namespace Liberu\Billing\Collections\Livewire\Components;

use Illuminate\View\View;
use Liberu\Billing\Collections\Actions\OpenCollectionCase;
use Liberu\Billing\Collections\Queries\ListCollectionCases;
use Livewire\Component;

final class CollectionCaseList extends Component
{
    public int $amountMinor = 0;

    public string $currency = 'USD';

    public bool $showCreate = false;

    public function create(OpenCollectionCase $open): void
    {
        $this->validate(['amountMinor' => ['required', 'integer', 'min:1'], 'currency' => ['required', 'string', 'size:3', 'alpha']]);
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $open->execute(['team_id' => $teamId, 'amount_minor' => $this->amountMinor, 'currency' => $this->currency]);
        $this->reset(['amountMinor', 'showCreate']);
        session()->flash('module-billing-collections-message', __('Collection case opened.'));
    }

    public function render(ListCollectionCases $query): View
    {
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return view('module-billing-collections-livewire::case-list', ['cases' => $query->execute($teamId === null ? null : (int) $teamId)]);
    }
}
