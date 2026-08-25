<section aria-labelledby="module-billing-domains-tlds-heading">
    <h2 id="module-billing-domains-tlds-heading">{{ __('Domain TLD pricing') }}</h2>
    @if (session('module-billing-domains-message'))<p role="status">{{ session('module-billing-domains-message') }}</p>@endif
    <button type="button" wire:click="$toggle('showCreate')">{{ __('Add TLD') }}</button>
    @if ($showCreate)
        <form wire:submit="saveTld">
            <label>{{ __('TLD') }} <input wire:model="name" type="text" placeholder=".com" required></label>
            <label>{{ __('Base price') }} <input wire:model="basePrice" type="number" min="0" step="0.01" required></label>
            <label>{{ __('Markup type') }} <select wire:model="markupType"><option value="none">{{ __('None') }}</option><option value="percentage">{{ __('Percentage') }}</option><option value="fixed">{{ __('Fixed') }}</option></select></label>
            <label>{{ __('Markup value') }} <input wire:model="markupValue" type="number" min="0" step="0.01" required></label>
            @error('name')<span role="alert">{{ $message }}</span>@enderror
            <button type="submit">{{ __('Save') }}</button>
        </form>
    @endif
    <ul>
        @foreach ($tlds as $tld)<li wire:key="domain-tld-{{ $tld->id }}">{{ $tld->name }} — {{ $tld->calculatePrice() }}</li>@endforeach
    </ul>
</section>
