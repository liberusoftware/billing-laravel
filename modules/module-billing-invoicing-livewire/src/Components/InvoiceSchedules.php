<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Invoicing\Actions\CreateInvoiceSchedule;
use Liberu\Billing\Invoicing\Actions\RunInvoiceSchedule;
use Liberu\Billing\Invoicing\Models\InvoiceSchedule;
use Livewire\Component;

final class InvoiceSchedules extends Component
{
    public string $frequency = 'monthly';

    public string $nextRunAt = '';

    public bool $active = true;

    public ?int $selectedScheduleId = null;

    public function createSchedule(CreateInvoiceSchedule $create): void
    {
        Gate::authorize('create', InvoiceSchedule::class);
        $this->validate(['frequency' => ['required', 'in:daily,weekly,monthly,yearly'], 'nextRunAt' => ['nullable', 'date'], 'active' => ['boolean']]);
        $create->execute(['team_id' => $this->teamId(), 'frequency' => $this->frequency, 'next_run_at' => $this->nextRunAt ?: now(), 'active' => $this->active]);
        $this->reset(['nextRunAt', 'selectedScheduleId']);
        session()->flash('module-billing-invoicing-schedules-message', __('Invoice schedule created.'));
    }

    public function runSchedule(RunInvoiceSchedule $run): void
    {
        $this->validate(['selectedScheduleId' => ['required', 'integer', 'min:1']]);
        $schedule = $this->schedule();
        Gate::authorize('update', $schedule);
        $run->execute($schedule);
        $this->reset('selectedScheduleId');
        session()->flash('module-billing-invoicing-schedules-message', __('Invoice schedule executed.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', InvoiceSchedule::class);

        return view('module-billing-invoicing-livewire::schedules', ['schedules' => InvoiceSchedule::query()->where(fn ($query) => $this->teamId() === null ? $query->whereNull('team_id') : $query->whereNull('team_id')->orWhere('team_id', $this->teamId()))->latest()->get()]);
    }

    private function schedule(): InvoiceSchedule
    {
        return InvoiceSchedule::query()->whereKey($this->selectedScheduleId)->where(fn ($query) => $this->teamId() === null ? $query->whereNull('team_id') : $query->whereNull('team_id')->orWhere('team_id', $this->teamId()))->firstOrFail();
    }

    private function teamId(): ?int
    {
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return $team === null ? null : (int) $team;
    }
}
