<section aria-labelledby="billing-communications-providers-heading">
    <h2 id="billing-communications-providers-heading">{{ __('Communication providers') }}</h2>
    @if (session('billing-communications-providers-message'))<p role="status">{{ session('billing-communications-providers-message') }}</p>@endif
    <form wire:submit="createProvider">
        <label>{{ __('Name') }} <input wire:model="name" type="text" required></label>
        <label>{{ __('Driver') }} <input wire:model="driver" type="text" required></label>
        <button type="submit">{{ __('Create provider') }}</button>
    </form>
    <ul>
        @forelse ($providers as $provider)
            <li wire:key="communication-provider-{{ $provider->id }}">{{ $provider->name }} ({{ $provider->driver }})</li>
        @empty
            <li>{{ __('No communication providers found.') }}</li>
        @endforelse
    </ul>
    <form wire:submit="importUsage">
        <h3>{{ __('Import usage') }}</h3>
        <label>{{ __('Provider') }} <input wire:model="provider" type="text" required></label>
        <label>{{ __('Rows') }} <input wire:model="rows" type="number" min="1" required></label>
        <label>{{ __('Amount (minor units)') }} <input wire:model="totalAmountMinor" type="number" min="0" required></label>
        <label>{{ __('Currency') }} <input wire:model="currency" type="text" maxlength="3" required></label>
        <button type="submit">{{ __('Import usage') }}</button>
    </form>
    <ul>
        @forelse ($usageImports as $usageImport)
            <li wire:key="communication-usage-import-{{ $usageImport->id }}">{{ $usageImport->provider }} — {{ $usageImport->rows }} {{ __('rows') }} ({{ $usageImport->currency }})</li>
        @empty
            <li>{{ __('No usage imports found.') }}</li>
        @endforelse
    </ul>
</section>
