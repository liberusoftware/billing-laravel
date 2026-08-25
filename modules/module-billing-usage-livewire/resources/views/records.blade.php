<section aria-labelledby="module-billing-usage-records-heading" wire:loading.class="opacity-50">
    <h2 id="module-billing-usage-records-heading">{{ __('Usage records') }}</h2>
    <form wire:submit="rate">
        <label>{{ __('Meter') }} <select wire:model="selectedMeterId" required><option value="">{{ __('Select a meter') }}</option>@foreach ($meters as $meter)<option value="{{ $meter->id }}">{{ $meter->name }} ({{ $meter->unit }})</option>@endforeach</select></label>
        <label>{{ __('Quantity') }} <input type="number" step="0.0001" min="0" wire:model="ratingQuantity" required></label>
        <button type="submit">{{ __('Rate usage') }}</button>
    </form>
    <form wire:submit="correct"><input type="number" wire:model="selectedRecordId" placeholder="{{ __('Record ID') }}"><input type="number" step="0.0001" wire:model="correctionQuantity" placeholder="{{ __('Correction quantity') }}"><input wire:model="correctionEventKey" placeholder="{{ __('Correction event key') }}"><button type="submit">{{ __('Correct usage') }}</button></form>
    @if (session('module-billing-usage-message'))<p role="status">{{ session('module-billing-usage-message') }}</p>@endif
    <ul><li wire:loading>{{ __('Loading…') }}</li>@forelse ($records as $record)<li wire:key="usage-record-{{ $record->id }}">{{ $record->event_key }}: {{ $record->quantity }} ({{ $record->amount_minor }})</li>@empty<li>{{ __('No usage records found.') }}</li>@endforelse</ul>
</section>
