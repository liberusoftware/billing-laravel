<section aria-labelledby="module-billing-domains-support-heading">
    <h2 id="module-billing-domains-support-heading">{{ __('Domain support') }}</h2>
    @if (session('module-billing-domains-message'))<p role="status">{{ session('module-billing-domains-message') }}</p>@endif
    <form wire:submit="createContact">
        <h3>{{ __('Create contact') }}</h3>
        <label>{{ __('Handle') }} <input wire:model="handle" type="text" required></label>
        <label>{{ __('Name') }} <input wire:model="contactName" type="text" required></label>
        <label>{{ __('Email') }} <input wire:model="email" type="email" required></label>
        <button type="submit">{{ __('Save contact') }}</button>
    </form>
    <ul wire:loading.class="opacity-50">
        @forelse ($contacts as $contact)
            <li wire:key="domain-contact-{{ $contact->id }}">{{ $contact->handle }} — {{ $contact->name }} ({{ $contact->email }})</li>
        @empty
            <li>{{ __('No domain contacts found.') }}</li>
        @endforelse
    </ul>
    <form wire:submit="saveDnsRecord">
        <h3>{{ __('DNS record') }}</h3>
        <label>{{ __('Domain') }} <select wire:model="domainId" required><option value="">{{ __('Select a domain') }}</option>@foreach ($domains as $domain)<option value="{{ $domain->id }}">{{ $domain->name }}</option>@endforeach</select></label>
        <label>{{ __('Type') }} <select wire:model="dnsType">@foreach (['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS'] as $type)<option value="{{ $type }}">{{ $type }}</option>@endforeach</select></label>
        <label>{{ __('Host') }} <input wire:model="dnsHost" type="text" required></label>
        <label>{{ __('Value') }} <input wire:model="dnsValue" type="text" required></label>
        <label>{{ __('TTL') }} <input wire:model="dnsTtl" type="number" min="60" required></label>
        <button type="submit">{{ __('Save DNS record') }}</button>
    </form>
    <ul>
        @forelse ($dnsRecords as $record)
            <li wire:key="domain-dns-record-{{ $record->id }}">{{ $record->type }} {{ $record->host }} → {{ $record->value }} ({{ $record->ttl }})</li>
        @empty
            <li>{{ __('No DNS records found.') }}</li>
        @endforelse
    </ul>
    <form wire:submit="recordEppOperation">
        <h3>{{ __('EPP operation') }}</h3>
        <label>{{ __('Domain') }} <select wire:model="domainId" required><option value="">{{ __('Select a domain') }}</option>@foreach ($domains as $domain)<option value="{{ $domain->id }}">{{ $domain->name }}</option>@endforeach</select></label>
        <label>{{ __('Operation') }} <input wire:model="eppOperation" type="text" maxlength="50" required></label>
        <label>{{ __('Status') }} <input wire:model="eppStatus" type="text" maxlength="50" required></label>
        <label>{{ __('EPP code') }} <input wire:model="eppCode" type="text" maxlength="100"></label>
        <label>{{ __('Payload (JSON)') }} <textarea wire:model="eppPayload"></textarea></label>
        <button type="submit">{{ __('Record operation') }}</button>
    </form>
    <ul>
        @forelse ($eppOperations as $operation)
            <li wire:key="domain-epp-operation-{{ $operation->id }}">{{ $operation->operation }} — {{ $operation->status }}</li>
        @empty
            <li>{{ __('No EPP operations found.') }}</li>
        @endforelse
    </ul>
</section>
