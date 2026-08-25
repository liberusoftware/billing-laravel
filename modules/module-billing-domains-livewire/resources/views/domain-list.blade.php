<section aria-labelledby="module-billing-domains-heading">
    <h2 id="module-billing-domains-heading">{{ __('Domains') }}</h2>
    @if (session('module-billing-domains-message'))<p role="status">{{ session('module-billing-domains-message') }}</p>@endif
    <button type="button" wire:click="$toggle('showCreate')">{{ __('Add domain') }}</button>
    @if ($showCreate)
        <form wire:submit="createDomain">
            <label>{{ __('Domain') }} <input wire:model="name" type="text" required></label>
            <label>{{ __('Registrar') }} <input wire:model="registrar" type="text"></label>
            @error('name')<span role="alert">{{ $message }}</span>@enderror
            <button type="submit">{{ __('Save') }}</button>
        </form>
    @endif
    <ul>
        @foreach ($domains as $domain)<li>{{ $domain->name }} — {{ $domain->status }} <input wire:model="customerId" placeholder="{{ __('Customer ID') }}"><button type="button" wire:click="register({{ $domain->id }})">{{ __('Register') }}</button><input wire:model="renewalPeriod" type="number" min="1" max="10" aria-label="{{ __('Renewal period') }}"><button type="button" wire:click="renew({{ $domain->id }})">{{ __('Renew') }}</button><input wire:model="authCode" placeholder="{{ __('EPP auth code') }}"><button type="button" wire:click="transfer({{ $domain->id }})">{{ __('Transfer') }}</button><button type="button" wire:click="redeem({{ $domain->id }})">{{ __('Redeem') }}</button></li>@endforeach
    </ul>
</section>
