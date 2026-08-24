<div>
    @if (session('module-billing-collections-message'))<div>{{ session('module-billing-collections-message') }}</div>@endif
    <button type="button" wire:click="$set('showCreate', true)">{{ __('Open collection case') }}</button>
    @if ($showCreate)
        <form wire:submit="create">
            <input wire:model="amountMinor" type="number" min="1" aria-label="{{ __('Amount') }}">
            <input wire:model="currency" maxlength="3" aria-label="{{ __('Currency') }}">
            <button type="submit">{{ __('Open') }}</button>
        </form>
    @endif
    <ul>@foreach ($cases as $case)<li>{{ $case->id }} — {{ $case->status->value }} — {{ $case->amount_minor }} {{ $case->currency }}</li>@endforeach</ul>
</div>
