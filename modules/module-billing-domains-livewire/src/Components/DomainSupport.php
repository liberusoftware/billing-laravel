<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Domains\Actions\CreateDomainContact;
use Liberu\Billing\Domains\Actions\RecordEppOperation;
use Liberu\Billing\Domains\Actions\UpsertDnsRecord;
use Liberu\Billing\Domains\Models\DnsRecord;
use Liberu\Billing\Domains\Models\Domain;
use Liberu\Billing\Domains\Models\DomainContact;
use Liberu\Billing\Domains\Models\EppOperation;
use Livewire\Component;

final class DomainSupport extends Component
{
    public string $handle = '';

    public string $contactName = '';

    public string $email = '';

    public string $domainId = '';

    public string $dnsType = 'A';

    public string $dnsHost = '';

    public string $dnsValue = '';

    public int $dnsTtl = 3600;

    public string $eppOperation = '';

    public string $eppStatus = 'pending';

    public string $eppCode = '';

    public string $eppPayload = '';

    public function createContact(CreateDomainContact $create): void
    {
        Gate::authorize('create', DomainContact::class);
        $this->validate([
            'handle' => ['required', 'string', 'max:64'],
            'contactName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);
        $create->execute($this->teamId(), ['handle' => $this->handle, 'name' => $this->contactName, 'email' => $this->email]);
        $this->reset(['handle', 'contactName', 'email']);
        session()->flash('module-billing-domains-message', __('Domain contact created.'));
    }

    public function saveDnsRecord(UpsertDnsRecord $upsert): void
    {
        $domain = $this->authorizedDomain();
        $this->validate([
            'dnsType' => ['required', 'in:A,AAAA,CNAME,MX,TXT,NS'],
            'dnsHost' => ['required', 'string', 'max:255'],
            'dnsValue' => ['required', 'string'],
            'dnsTtl' => ['required', 'integer', 'min:60'],
        ]);
        $upsert->execute($this->teamId(), ['domain_id' => $domain->id, 'type' => $this->dnsType, 'host' => $this->dnsHost, 'value' => $this->dnsValue, 'ttl' => $this->dnsTtl]);
        $this->reset(['dnsHost', 'dnsValue']);
        session()->flash('module-billing-domains-message', __('DNS record saved.'));
    }

    public function recordEppOperation(RecordEppOperation $record): void
    {
        $domain = $this->authorizedDomain();
        $this->validate([
            'eppOperation' => ['required', 'string', 'max:50'],
            'eppStatus' => ['required', 'string', 'max:50'],
            'eppCode' => ['nullable', 'string', 'max:100'],
            'eppPayload' => ['nullable', 'json'],
        ]);
        $payload = $this->eppPayload === '' ? [] : json_decode($this->eppPayload, true, 512, JSON_THROW_ON_ERROR);
        $record->execute($domain, $this->eppOperation, $this->eppStatus, $payload, $this->eppCode ?: null);
        $this->reset(['eppOperation', 'eppCode', 'eppPayload']);
        $this->eppStatus = 'pending';
        session()->flash('module-billing-domains-message', __('EPP operation recorded.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', DomainContact::class);
        $team = $this->teamId();

        return view('module-billing-domains-livewire::domain-support', [
            'contacts' => DomainContact::query()->where('team_id', $team)->latest()->get(),
            'domains' => Domain::query()->forTeam($team)->latest()->get(),
            'dnsRecords' => DnsRecord::query()->where('team_id', $team)->latest()->get(),
            'eppOperations' => EppOperation::query()->where('team_id', $team)->latest()->get(),
        ]);
    }

    private function authorizedDomain(): Domain
    {
        $domain = Domain::query()->forTeam($this->teamId())->findOrFail((int) $this->domainId);
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
