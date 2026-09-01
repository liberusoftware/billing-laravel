<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Payments\Actions\CreatePayment;
use Liberu\Billing\Payments\Models\Payment;
use Liberu\Billing\Payments\Queries\ListPayments;
use Livewire\Component;

final class PaymentList extends Component
{
    public int $amountMinor = 0;

    public string $currency = 'USD';

    public bool $showCreate = false;

    public function createPayment(CreatePayment $create): void
    {
        Gate::authorize('create', Payment::class);
        $this->validate([
            'amountMinor' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
        ]);
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $create->execute(['team_id' => $teamId, 'amount_minor' => $this->amountMinor, 'currency' => $this->currency]);
        $this->reset('amountMinor', 'showCreate');
        session()->flash('module-billing-payments-message', __('Payment created.'));
    }

    public function render(ListPayments $query): View
    {
        Gate::authorize('viewAny', Payment::class);
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return view('module-billing-payments-livewire::payment-list', ['payments' => $query->execute($teamId === null ? null : (int) $teamId)]);
    }
}
