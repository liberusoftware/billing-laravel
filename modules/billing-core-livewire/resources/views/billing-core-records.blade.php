<section aria-labelledby="billing-core-records-heading">
    <h2 id="billing-core-records-heading">{{ __('Billing Core records') }}</h2>

    @if (session()->has('billing-core-record-message'))
        <p role="status">{{ session('billing-core-record-message') }}</p>
    @endif

    <label>
        {{ __('Record type') }}
        <select wire:model.live="type">
            <option value="contacts">{{ __('Contacts') }}</option>
            <option value="currencies">{{ __('Currencies') }}</option>
            <option value="tax-profiles">{{ __('Tax profiles') }}</option>
            <option value="sequences">{{ __('Sequences') }}</option>
            <option value="terms">{{ __('Terms') }}</option>
            <option value="settings">{{ __('Billing settings') }}</option>
        </select>
    </label>
    <button type="button" wire:click="$set('showCreate', true)">{{ __('Create') }}</button>

    @if ($showCreate)
        <form wire:submit="save">
            @if ($type === 'contacts' || $type === 'tax-profiles' || $type === 'sequences' || $type === 'terms')
                <label>{{ __('Name') }} <input wire:model="name" required></label>
                @error('name') <p role="alert">{{ $message }}</p> @enderror
            @endif
            @if ($type === 'contacts')
                <label>{{ __('Email') }} <input wire:model="email" type="email"></label>
            @elseif ($type === 'currencies')
                <label>{{ __('ISO code') }} <input wire:model="code" maxlength="3" required></label>
            @elseif ($type === 'tax-profiles')
                <label>{{ __('Rate') }} <input wire:model="rate" type="number" min="0" max="100" step="0.00001" required></label>
            @elseif ($type === 'sequences')
                <label>{{ __('Prefix') }} <input wire:model="prefix"></label>
                <label>{{ __('Next number') }} <input wire:model="nextNumber" type="number" min="1" required></label>
            @elseif ($type === 'terms')
                <label>{{ __('Due days') }} <input wire:model="dueDays" type="number" min="0" required></label>
            @elseif ($type === 'settings')
                <label>{{ __('Values (JSON)') }} <textarea wire:model="valuesJson"></textarea></label>
            @endif
            <button type="submit">{{ __('Save') }}</button>
            <button type="button" wire:click="$set('showCreate', false)">{{ __('Cancel') }}</button>
        </form>
    @endif

    <ul wire:loading.remove>
        @forelse ($records as $record)
            <li wire:key="billing-core-record-{{ $record->getKey() }}">{{ $record->name ?? $record->code ?? $record->id }}</li>
        @empty
            <li>{{ __('No records found.') }}</li>
        @endforelse
    </ul>
    <p wire:loading role="status">{{ __('Loading…') }}</p>
</section>
