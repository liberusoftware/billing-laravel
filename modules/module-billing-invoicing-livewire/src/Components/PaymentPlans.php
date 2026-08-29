<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Invoicing\Actions\CreatePaymentPlan;
use Liberu\Billing\Invoicing\Actions\RunPaymentPlan;
use Liberu\Billing\Invoicing\Models\Invoice;
use Liberu\Billing\Invoicing\Models\PaymentPlan;
use Livewire\Component;

final class PaymentPlans extends Component
{
    public ?int $invoiceId = null;

    public int $totalInstallments = 2;

    public string $frequency = 'monthly';

    public ?int $selectedPlanId = null;

    public function createPlan(CreatePaymentPlan $create): void
    {
        Gate::authorize('create', Invoice::class);
        $this->validate(['invoiceId' => ['required', 'integer', 'min:1'], 'totalInstallments' => ['required', 'integer', 'min:2'], 'frequency' => ['required', 'in:weekly,monthly,quarterly']]);
        $create->execute($this->invoice(), $this->totalInstallments, $this->frequency);
        $this->reset(['invoiceId', 'selectedPlanId']);
        session()->flash('module-billing-invoicing-payment-plans-message', __('Payment plan created.'));
    }

    public function runPlan(RunPaymentPlan $run): void
    {
        $this->validate(['selectedPlanId' => ['required', 'integer', 'min:1']]);
        $plan = PaymentPlan::query()->whereKey($this->selectedPlanId)->where('team_id', $this->teamId())->firstOrFail();
        Gate::authorize('update', $plan->invoice);
        $run->execute($plan);
        $this->reset('selectedPlanId');
        session()->flash('module-billing-invoicing-payment-plans-message', __('Payment plan installment created.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Invoice::class);
        $teamId = $this->teamId();
        $scope = fn ($query) => $teamId === null ? $query->whereNull('team_id') : $query->where('team_id', $teamId);

        return view('module-billing-invoicing-livewire::payment-plans', ['invoices' => Invoice::query()->where($scope)->where('status', 'finalized')->latest()->get(), 'plans' => PaymentPlan::query()->where($scope)->latest()->get()]);
    }

    private function invoice(): Invoice
    {
        return Invoice::query()->whereKey($this->invoiceId)->where('team_id', $this->teamId())->firstOrFail();
    }

    private function teamId(): ?int
    {
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return $team === null ? null : (int) $team;
    }
}
