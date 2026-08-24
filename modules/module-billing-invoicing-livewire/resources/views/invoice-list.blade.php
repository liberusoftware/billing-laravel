<div>
    @if (session('module-billing-invoicing-message'))<div>{{ session('module-billing-invoicing-message') }}</div>@endif
    <button type="button" wire:click="$set('showCreate', true)">{{ __('New invoice') }}</button>
    @if ($showCreate)
        <form wire:submit="create">
            <input wire:model="currency" maxlength="3" aria-label="{{ __('Currency') }}">
            @error('currency')<span>{{ $message }}</span>@enderror
            <button type="submit">{{ __('Create draft') }}</button>
        </form>
    @endif
    <ul>@foreach ($invoices as $invoice)<li>{{ $invoice->id }} — {{ $invoice->status->value }} — {{ $invoice->total_minor }} {{ $invoice->currency }}</li>@endforeach</ul>
</div>
