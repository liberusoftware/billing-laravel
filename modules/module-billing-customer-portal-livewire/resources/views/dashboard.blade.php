<section wire:loading.class="opacity-50" aria-labelledby="customer-portal-dashboard-heading">
    <h2 id="customer-portal-dashboard-heading">{{ __('Customer portal') }}</h2>
    <label>{{ __('Area') }} <select wire:model.live="type"><option value="">{{ __('All areas') }}</option>@foreach (['profile', 'orders', 'services', 'usage', 'invoices', 'payments', 'tickets', 'changes', 'cancellation'] as $area)<option value="{{ $area }}">{{ ucfirst($area) }}</option>@endforeach</select></label>
    <ul>
        @forelse ($items as $item)
            <li wire:key="portal-dashboard-item-{{ $item->id }}">{{ ucfirst($item->type) }}: {{ $item->subject }} ({{ $item->status }})</li>
        @empty
            <li>{{ __('No portal items found.') }}</li>
        @endforelse
    </ul>
    {{ $items->links() }}
</section>
