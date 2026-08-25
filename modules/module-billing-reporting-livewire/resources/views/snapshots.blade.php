<section aria-labelledby="billing-reporting-snapshots-heading" wire:loading.class="opacity-50">
    <h2 id="billing-reporting-snapshots-heading">{{ __('Metric snapshots') }}</h2>
    @if (session()->has('module-billing-reporting-snapshot-message'))
        <p role="status">{{ session('module-billing-reporting-snapshot-message') }}</p>
    @endif
    <form wire:submit="createSnapshot">
        <label>{{ __('Name') }} <input wire:model="name" maxlength="255" required></label>
        <button type="submit">{{ __('Create snapshot') }}</button>
    </form>
    <ul>
        @forelse ($snapshots as $snapshot)
            <li wire:key="reporting-snapshot-{{ $snapshot->id }}">{{ $snapshot->name }} ({{ $snapshot->status }})</li>
        @empty
            <li>{{ __('No metric snapshots found.') }}</li>
        @endforelse
    </ul>
</section>
