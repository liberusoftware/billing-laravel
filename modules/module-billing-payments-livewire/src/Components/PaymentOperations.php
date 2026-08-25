<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Payments\Actions\OpenDispute;
use Liberu\Billing\Payments\Actions\ReconcilePayment;
use Liberu\Billing\Payments\Actions\RefundPayment;
use Liberu\Billing\Payments\Models\Payment;
use Liberu\Billing\Payments\Models\PaymentDispute;
use Liberu\Billing\Payments\Models\PaymentReconciliation;
use Livewire\Component;

final class PaymentOperations extends Component
{
    public ?int $selectedPaymentId = null;

    public int $amountMinor = 0;

    public string $reason = '';

    public string $providerReference = '';

    public function refund(RefundPayment $refund): void
    {
        $this->validate(['selectedPaymentId' => ['required', 'integer'], 'amountMinor' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'max:255']]);
        $payment = $this->paymentForCurrentTeam();
        Gate::authorize('update', $payment);
        $refund->execute($payment, $this->amountMinor, $this->reason);
        $this->resetOperationFields();
        session()->flash('module-billing-payments-operations-message', __('Payment refunded.'));
    }

    public function dispute(OpenDispute $dispute): void
    {
        $this->validate(['selectedPaymentId' => ['required', 'integer'], 'amountMinor' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'max:255']]);
        $payment = $this->paymentForCurrentTeam();
        Gate::authorize('update', $payment);
        $dispute->execute($payment, $this->amountMinor, $this->reason);
        $this->resetOperationFields();
        session()->flash('module-billing-payments-operations-message', __('Payment dispute opened.'));
    }

    public function reconcile(ReconcilePayment $reconcile): void
    {
        $this->validate(['selectedPaymentId' => ['required', 'integer'], 'providerReference' => ['required', 'string', 'max:255']]);
        $payment = $this->paymentForCurrentTeam();
        Gate::authorize('update', $payment);
        $reconcile->execute($payment, $this->providerReference);
        $this->resetOperationFields();
        session()->flash('module-billing-payments-operations-message', __('Payment reconciled.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', PaymentDispute::class);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));

        return view('module-billing-payments-livewire::operations', ['payments' => Payment::query()->where('team_id', $team)->latest()->get(), 'disputes' => PaymentDispute::query()->whereHas('payment', fn ($query) => $query->where('team_id', $team))->latest()->get(), 'reconciliations' => PaymentReconciliation::query()->whereHas('payment', fn ($query) => $query->where('team_id', $team))->latest()->get()]);
    }

    private function paymentForCurrentTeam(): Payment
    {
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return Payment::query()->whereKey($this->selectedPaymentId)->where('team_id', $team)->firstOrFail();
    }

    private function resetOperationFields(): void
    {
        $this->reset('selectedPaymentId', 'amountMinor', 'reason', 'providerReference');
    }
}
