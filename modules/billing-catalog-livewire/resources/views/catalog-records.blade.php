<div>
    @if (session('billing-catalog-message')) <div role="status">{{ session('billing-catalog-message') }}</div> @endif
    <select wire:model.live="type" aria-label="Catalog type"><option value="plans">Plans</option><option value="addons">Add-ons</option><option value="bundles">Bundles</option><option value="options">Options</option><option value="eligibility">Eligibility</option><option value="channels">Channels</option></select>
    <button type="button" wire:click="$set('showCreate', true)">{{ __('Create') }}</button>
    <label>{{ __('Lifecycle status') }} <select wire:model="transitionStatus"><option value="draft">Draft</option><option value="active">Active</option><option value="archived">Archived</option></select></label>
    <ul wire:loading.class="opacity-50"><li wire:loading>{{ __('Loading…') }}</li>@forelse ($records as $record)<li wire:key="catalog-record-{{ $record->getKey() }}">{{ $record->name }} ({{ $record->code }}) — {{ $record->status->value }} <button type="button" wire:click="transitionRecord({{ $record->getKey() }})">{{ __('Update lifecycle') }}</button></li>@empty<li>{{ __('No records found.') }}</li>@endforelse</ul>
</div>
