<?php

declare(strict_types=1);

namespace Liberu\Billing\Collections\Livewire\Components;

use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Collections\Actions\OpenCollectionCase;
use Liberu\Billing\Collections\Actions\ApplyCreditControl;
use Liberu\Billing\Collections\Actions\PromisePayment;
use Liberu\Billing\Collections\Actions\RecoverCollectionCase;
use Liberu\Billing\Collections\Actions\RetryCollectionCase;
use Liberu\Billing\Collections\Actions\ScheduleDunning;
use Liberu\Billing\Collections\Actions\ScheduleReminder;
use Liberu\Billing\Collections\Actions\SuspendCollectionCase;
use Liberu\Billing\Collections\Actions\WriteOffCollectionCase;
use Liberu\Billing\Collections\Models\CollectionCase;
use Liberu\Billing\Collections\Queries\ListCollectionCases;
use Livewire\Component;

final class CollectionCaseList extends Component
{
    public int $amountMinor = 0;

    public string $currency = 'USD';

    public bool $showCreate = false;

    public string $operationDate = '';

    public string $operationReason = '';

    public string $creditControlLevel = '';

    public function promise(int $caseId, PromisePayment $promise): void
    {
        $this->validateOperationDate();
        $promise->execute($this->authorizedCase($caseId), new \DateTimeImmutable($this->operationDate));
        $this->resetOperation();
    }

    public function retry(int $caseId, RetryCollectionCase $retry): void
    {
        $this->validateOperationDate();
        $retry->execute($this->authorizedCase($caseId), new \DateTimeImmutable($this->operationDate));
        $this->resetOperation();
    }

    public function dunning(int $caseId, ScheduleDunning $dunning): void
    {
        $this->validateOperationDate();
        $dunning->execute($this->authorizedCase($caseId), new \DateTimeImmutable($this->operationDate));
        $this->resetOperation();
    }

    public function reminder(int $caseId, ScheduleReminder $reminder): void
    {
        $this->validateOperationDate();
        $reminder->execute($this->authorizedCase($caseId), new \DateTimeImmutable($this->operationDate));
        $this->resetOperation();
    }

    public function suspend(int $caseId, SuspendCollectionCase $suspend): void
    {
        $this->validate(['operationReason' => ['required', 'string', 'max:1000']]);
        $suspend->execute($this->authorizedCase($caseId), $this->operationReason);
        $this->resetOperation();
    }

    public function writeOff(int $caseId, WriteOffCollectionCase $writeOff): void
    {
        $this->validate(['operationReason' => ['required', 'string', 'max:1000']]);
        $writeOff->execute($this->authorizedCase($caseId), $this->operationReason);
        $this->resetOperation();
    }

    public function recover(int $caseId, RecoverCollectionCase $recover): void
    {
        $recover->execute($this->authorizedCase($caseId));
        session()->flash('module-billing-collections-message', __('Collection case recovered.'));
    }

    public function creditControl(int $caseId, ApplyCreditControl $creditControl): void
    {
        $this->validate(['creditControlLevel' => ['required', 'string', 'max:50'], 'operationReason' => ['nullable', 'string', 'max:1000']]);
        $creditControl->execute($this->authorizedCase($caseId), $this->creditControlLevel, $this->operationReason ?: null);
        $this->resetOperation();
    }

    public function create(OpenCollectionCase $open): void
    {
        Gate::authorize('create', CollectionCase::class);
        $this->validate(['amountMinor' => ['required', 'integer', 'min:1'], 'currency' => ['required', 'string', 'size:3', 'alpha']]);
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $open->execute(['team_id' => $teamId, 'amount_minor' => $this->amountMinor, 'currency' => $this->currency]);
        $this->reset(['amountMinor', 'showCreate']);
        session()->flash('module-billing-collections-message', __('Collection case opened.'));
    }

    public function render(ListCollectionCases $query): View
    {
        Gate::authorize('viewAny', CollectionCase::class);
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return view('module-billing-collections-livewire::case-list', ['cases' => $query->execute($teamId === null ? null : (int) $teamId)]);
    }

    private function authorizedCase(int $caseId): CollectionCase
    {
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $case = CollectionCase::query()->whereKey($caseId)->where('team_id', $teamId)->firstOrFail();
        Gate::authorize('update', $case);

        return $case;
    }

    private function validateOperationDate(): void
    {
        $this->validate(['operationDate' => ['required', 'date', 'after:now']]);
    }

    private function resetOperation(): void
    {
        $this->reset(['operationDate', 'operationReason', 'creditControlLevel']);
        session()->flash('module-billing-collections-message', __('Collection operation applied.'));
    }
}
