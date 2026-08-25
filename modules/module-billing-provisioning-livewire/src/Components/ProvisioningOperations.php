<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Livewire\Components;

use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Provisioning\Actions\QueueProvisioningOperation;
use Liberu\Billing\Provisioning\Actions\ReconcileProvisionedService;
use Liberu\Billing\Provisioning\Models\ProvisionedService;
use Liberu\Billing\Provisioning\Models\ProvisioningOperation;
use Livewire\Component;

final class ProvisioningOperations extends Component
{
    public string $operation = 'provision';

    public ?int $selectedServiceId = null;

    public function queue(QueueProvisioningOperation $queue): void
    {
        $this->validate([
            'selectedServiceId' => ['required', 'integer', 'exists:billing_provisioned_services,id'],
            'operation' => ['required', 'in:provision,deprovision,poll,reconcile,rollback'],
        ]);

        $service = $this->serviceForCurrentTeam();
        Gate::authorize('update', $service);
        $queue->execute($service, $this->operation);
        $this->reset('selectedServiceId');
        session()->flash('module-billing-provisioning-message', __('Provisioning operation queued.'));
    }

    public function reconcile(ReconcileProvisionedService $reconcile): void
    {
        $this->validate(['selectedServiceId' => ['required', 'integer']]);
        $service = $this->serviceForCurrentTeam();
        Gate::authorize('update', $service);
        $reconcile->execute($service);
        session()->flash('module-billing-provisioning-message', __('Provisioned service reconciled.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', ProvisioningOperation::class);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));

        return view('module-billing-provisioning-livewire::operations', [
            'services' => ProvisionedService::query()->where('team_id', $team)->latest()->get(),
            'operations' => ProvisioningOperation::query()->where('team_id', $team)->latest()->get(),
        ]);
    }

    private function serviceForCurrentTeam(): ProvisionedService
    {
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return ProvisionedService::query()
            ->whereKey($this->selectedServiceId)
            ->where('team_id', $team)
            ->firstOrFail();
    }
}
