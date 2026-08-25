<section aria-labelledby="customer-portal-items-heading" wire:loading.class="opacity-50">
    <h2 id="customer-portal-items-heading">{{ __('Portal items') }}</h2>
    @if (session()->has('billing-customer-portal-message'))
        <p role="status">{{ session('billing-customer-portal-message') }}</p>
    @endif
    <form wire:submit="createItem">
        <label>{{ __('Area') }}
            <select wire:model="type">
                @foreach (['profile', 'orders', 'services', 'usage', 'invoices', 'payments', 'tickets', 'changes', 'cancellation'] as $area)
                    <option value="{{ $area }}">{{ ucfirst($area) }}</option>
                @endforeach
            </select>
        </label>
        <label>{{ __('Subject') }} <input wire:model="subject" maxlength="255" required></label>
        <label>{{ __('Customer ID') }} <input type="number" min="1" wire:model="customerId"></label>
        <button type="submit">{{ __('Create item') }}</button>
    </form>
    <ul>
        @forelse ($items as $item)
            <li wire:key="portal-item-{{ $item->id }}">{{ $item->type }}: {{ $item->subject }} ({{ $item->status }}) <button type="button" wire:click="$set('selectedItemId', {{ $item->id }})">{{ __('Select') }}</button></li>
        @empty
            <li>{{ __('No portal items found.') }}</li>
        @endforelse
    </ul>
    @if ($selectedItemId)
        <form wire:submit="transition">
            <select wire:model="status"><option value="open">{{ __('Open') }}</option><option value="in_progress">{{ __('In progress') }}</option><option value="completed">{{ __('Completed') }}</option><option value="cancelled">{{ __('Cancelled') }}</option><option value="failed">{{ __('Failed') }}</option></select>
            <button type="submit">{{ __('Update status') }}</button>
        </form>
    @endif
</section>
