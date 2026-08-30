<div>
    @if (session('module-billing-usage-message'))<div role="status">{{ session('module-billing-usage-message') }}</div>@endif
    <button type="button" wire:click="$set('showCreate', true)">{{ __('Define meter') }}</button>
    @if ($showCreate)
        <form wire:submit="createMeter">
            <input wire:model="name" type="text" aria-label="{{ __('Name') }}">
            <input wire:model="code" type="text" aria-label="{{ __('Code') }}">
            <input wire:model="unit" type="text" aria-label="{{ __('Unit') }}">
            <input wire:model="unitPriceMinor" type="number" min="0" aria-label="{{ __('Unit price') }}">
            <input wire:model="currency" maxlength="3" aria-label="{{ __('Currency') }}">
            @error('code')<span>{{ $message }}</span>@enderror
            <button type="submit">{{ __('Create') }}</button>
        </form>
    @endif
    <form wire:submit="ingest">
        <select wire:model="selectedMeterId" aria-label="{{ __('Meter') }}"><option value="">{{ __('Choose a meter') }}</option>@foreach ($meters as $meter)<option wire:key="meter-{{ $meter->id }}" value="{{ $meter->id }}">{{ $meter->code }}</option>@endforeach</select>
        <input wire:model="eventKey" type="text" aria-label="{{ __('Event key') }}">
        <input wire:model="quantity" type="number" min="0.0001" step="0.0001" aria-label="{{ __('Quantity') }}">
        <button type="submit">{{ __('Record usage') }}</button>
    </form>
    <ul>@forelse ($meters as $meter)<li wire:key="meter-row-{{ $meter->id }}">{{ $meter->name }} — {{ $meter->unit_price_minor }} {{ $meter->currency }} ({{ $meter->active ? __('Active') : __('Inactive') }}) <button type="button" wire:click="transition({{ $meter->id }})">{{ __('Update status') }}</button></li>@empty<li>{{ __('No meters defined.') }}</li>@endforelse</ul>
</div>
