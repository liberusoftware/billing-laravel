<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Provisioning\Actions\CreateProvisionedService;
use Liberu\Billing\Provisioning\Actions\QueueProvisioningOperation;
use Liberu\Billing\Provisioning\Actions\ReconcileProvisionedService;
use Liberu\Billing\Provisioning\Actions\TransitionProvisionedService;
use Liberu\Billing\Provisioning\Enums\ProvisioningState;
use Liberu\Billing\Provisioning\Models\ProvisionedService;
use Liberu\Billing\Provisioning\Models\ProvisioningOperation;
use Livewire\Component;

final class ProvisioningOperations extends Component
{
    public string $operation = 'provision';

    public ?int $selectedServiceId = null;

    public string $provider = '';

    public string $externalId = '';

    public string $state = 'pending';

    public string $lastError = '';

    public function createService(CreateProvisionedService $create): void
    {
        Gate::authorize('create', ProvisionedService::class);
        $this->validate(['provider' => ['required', 'string', 'max:100'], 'externalId' => ['nullable', 'string', 'max:255']]);
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $create->execute(['team_id' => $team, 'provider' => $this->provider, 'external_id' => $this->externalId ?: null]);
        $this->reset(['provider', 'externalId']);
        session()->flash('module-billing-provisioning-message', __('Provisioned service created.'));
    }

    public function transition(TransitionProvisionedService $transition): void
    {
        $this->validate(['selectedServiceId' => ['required', 'integer'], 'state' => ['required', 'in:pending,provisioning,active,suspended,failed,deprovisioning,deprovisioned'], 'lastError' => ['nullable', 'string', 'max:2000']]);
        $service = $this->serviceForCurrentTeam();
        Gate::authorize('update', $service);
        $transition->execute($service, ProvisioningState::from($this->state), $this->lastError ?: null);
        $this->reset(['selectedServiceId', 'lastError']);
        session()->flash('module-billing-provisioning-message', __('Provisioned service state updated.'));
    }

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
