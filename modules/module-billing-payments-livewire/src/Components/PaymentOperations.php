<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Livewire\Components;

use Illuminate\View\View;
use Liberu\Billing\Payments\Models\PaymentDispute;
use Liberu\Billing\Payments\Models\PaymentReconciliation;
use Livewire\Component;

final class PaymentOperations extends Component
{
    public function render(): View
    {
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));

        return view('module-billing-payments-livewire::operations', ['disputes' => PaymentDispute::query()->whereHas('payment', fn ($query) => $query->where('team_id', $team))->latest()->get(), 'reconciliations' => PaymentReconciliation::query()->whereHas('payment', fn ($query) => $query->where('team_id', $team))->latest()->get()]);
    }
}
