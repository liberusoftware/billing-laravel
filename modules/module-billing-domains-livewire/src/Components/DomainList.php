<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Domains\Actions\CreateDomain;
use Liberu\Billing\Domains\Actions\RedeemDomain;
use Liberu\Billing\Domains\Actions\RegisterDomain;
use Liberu\Billing\Domains\Actions\RenewDomain;
use Liberu\Billing\Domains\Actions\TransferDomain;
use Liberu\Billing\Domains\Actions\UpdateDomain;
use Liberu\Billing\Domains\Models\Domain;
use Liberu\Billing\Domains\Queries\ListDomainsRecords;
use Livewire\Component;

final class DomainList extends Component
{
    public string $name = '';

    public string $registrar = '';

    public bool $showCreate = false;

    public string $customerId = '';

    public int $renewalPeriod = 1;

    public string $authCode = '';

    public ?int $selectedDomainId = null;

    public string $domainStatus = '';

    public string $domainRegistrar = '';

    public function updateDomain(UpdateDomain $update): void
    {
        $this->validate(['selectedDomainId' => ['required', 'integer', 'min:1'], 'domainStatus' => ['required', 'string', 'max:50'], 'domainRegistrar' => ['nullable', 'string', 'max:100']]);
        $domain = $this->authorizedDomain((int) $this->selectedDomainId);
        $update->handle($domain, ['status' => $this->domainStatus, 'registrar' => $this->domainRegistrar ?: null]);
        $this->reset(['selectedDomainId', 'domainStatus', 'domainRegistrar']);
        session()->flash('module-billing-domains-message', __('Domain updated.'));
    }

    public function register(int $domainId, RegisterDomain $register): void
    {
        $this->validate(['customerId' => ['required', 'integer', 'min:1']]);
        $register->execute($this->authorizedDomain($domainId), (int) $this->customerId);
        $this->reset('customerId');
        session()->flash('module-billing-domains-message', __('Domain registered.'));
    }

    public function renew(int $domainId, RenewDomain $renew): void
    {
        $this->validate(['renewalPeriod' => ['required', 'integer', 'min:1', 'max:10']]);
        $renew->execute($this->authorizedDomain($domainId), $this->renewalPeriod);
        session()->flash('module-billing-domains-message', __('Domain renewed.'));
    }

    public function transfer(int $domainId, TransferDomain $transfer): void
    {
        $this->validate(['authCode' => ['required', 'string', 'max:255'], 'customerId' => ['required', 'integer', 'min:1']]);
        $transfer->execute($this->authorizedDomain($domainId), $this->authCode, (int) $this->customerId);
        $this->reset(['authCode', 'customerId']);
        session()->flash('module-billing-domains-message', __('Domain transfer started.'));
    }

    public function redeem(int $domainId, RedeemDomain $redeem): void
    {
        $redeem->execute($this->authorizedDomain($domainId));
        session()->flash('module-billing-domains-message', __('Domain redemption requested.'));
    }

    public function createDomain(CreateDomain $create): void
    {
        Gate::authorize('create', Domain::class);
        $this->validate(['name' => ['required', 'string', 'max:255'], 'registrar' => ['nullable', 'string', 'max:100']]);
        $teamId = $this->teamId();
        $create->handle($teamId, ['name' => $this->name, 'registrar' => $this->registrar ?: null]);
        $this->reset(['name', 'registrar', 'showCreate']);
        session()->flash('module-billing-domains-message', __('Domain created.'));
    }

    public function render(ListDomainsRecords $query): View
    {
        $teamId = $this->teamId();

        return view('module-billing-domains-livewire::domain-list', ['domains' => $query->handle($teamId)]);
    }

    private function authorizedDomain(int $domainId): Domain
    {
        $domain = Domain::query()->forTeam($this->teamId())->findOrFail($domainId);
        Gate::authorize('update', $domain);

        return $domain;
    }

    private function teamId(): int
    {
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        abort_if($team === null, 403, 'A current team is required.');

        return (int) $team;
    }
}
