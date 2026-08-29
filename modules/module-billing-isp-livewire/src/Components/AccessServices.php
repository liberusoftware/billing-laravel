<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Isp\Actions\CreateAccessService;
use Liberu\Billing\Isp\Actions\RecordRadiusAccounting;
use Liberu\Billing\Isp\Actions\ResetUsagePeriod;
use Liberu\Billing\Isp\Actions\SynchronizeAccessService;
use Liberu\Billing\Isp\Actions\TransitionAccessService;
use Liberu\Billing\Isp\Models\AccessService;
use Livewire\Component;

final class AccessServices extends Component
{
    public string $name = '';

    public ?int $selectedServiceId = null;

    public string $status = 'active';

    public int $monthlyDataLimitBytes = 0;

    public string $adapter = '';

    public string $accountingSessionId = '';

    public string $accountingStartedAt = '';

    public int $inputBytes = 0;

    public int $outputBytes = 0;

    public function createService(CreateAccessService $create): void
    {
        Gate::authorize('create', AccessService::class);
        $this->validate(['name' => ['required', 'string', 'max:255'], 'monthlyDataLimitBytes' => ['nullable', 'integer', 'min:0']]);
        $team = $this->teamId();
        $create->handle($team, ['name' => $this->name, 'monthly_data_limit_bytes' => $this->monthlyDataLimitBytes ?: null]);
        $this->reset('name');
        session()->flash('isp-services-message', __('ISP access service created.'));
    }

    public function synchronize(SynchronizeAccessService $synchronize): void
    {
        $this->validate(['selectedServiceId' => ['required', 'integer'], 'adapter' => ['required', 'string', 'max:100']]);
        $service = $this->service();
        Gate::authorize('update', $service);
        $synchronize->execute($service, $this->adapter);
        session()->flash('isp-services-message', __('ISP access service synchronized.'));
    }

    public function recordAccounting(RecordRadiusAccounting $record): void
    {
        $this->validate(['selectedServiceId' => ['required', 'integer'], 'accountingSessionId' => ['required', 'string', 'max:255'], 'accountingStartedAt' => ['required', 'date'], 'inputBytes' => ['integer', 'min:0'], 'outputBytes' => ['integer', 'min:0']]);
        $service = $this->service();
        Gate::authorize('update', $service);
        $record->execute($service, ['accounting_session_id' => $this->accountingSessionId, 'started_at' => $this->accountingStartedAt, 'input_bytes' => $this->inputBytes, 'output_bytes' => $this->outputBytes]);
        session()->flash('isp-services-message', __('RADIUS accounting recorded.'));
    }

    public function resetUsage(ResetUsagePeriod $reset): void
    {
        $service = $this->service();
        Gate::authorize('update', $service);
        $reset->execute($service);
        session()->flash('isp-services-message', __('ISP usage period reset.'));
    }

    public function transitionService(TransitionAccessService $transition): void
    {
        $this->validate(['selectedServiceId' => ['required', 'integer'], 'status' => ['required', 'in:pending,active,suspended,cancelled,failed']]);
        $team = $this->teamId();
        $service = AccessService::query()->forTeam($team)->findOrFail($this->selectedServiceId);
        Gate::authorize('update', $service);
        $transition->handle($service, $this->status);
        session()->flash('isp-services-message', __('ISP access service status updated.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', AccessService::class);
        $team = $this->teamId();

        return view('module-billing-isp-livewire::services', ['services' => AccessService::query()->forTeam($team)->latest()->get()]);
    }

    private function teamId(): int
    {
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        abort_if($team === null, 403, 'A current team is required.');

        return (int) $team;
    }

    private function service(): AccessService
    {
        return AccessService::query()->forTeam($this->teamId())->findOrFail($this->selectedServiceId);
    }
}
