<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Livewire\Components;

use Illuminate\View\View;
use Liberu\Billing\Domains\Models\DomainContact;
use Livewire\Component;

final class DomainSupport extends Component
{
    public function render(): View
    {
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));

        return view('module-billing-domains-livewire::domain-support', ['contacts' => DomainContact::query()->where('team_id', $team)->latest()->get()]);
    }
}
