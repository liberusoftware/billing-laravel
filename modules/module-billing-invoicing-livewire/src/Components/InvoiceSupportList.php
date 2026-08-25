<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Invoicing\Models\InvoiceSupport;
use Livewire\Component;

final class InvoiceSupportList extends Component
{
    public function render(): View
    {
        Gate::authorize('viewAny', InvoiceSupport::class);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));

        return view('module-billing-invoicing-livewire::support-list', ['items' => InvoiceSupport::query()->where('team_id', $team)->latest()->get()]);
    }
}
