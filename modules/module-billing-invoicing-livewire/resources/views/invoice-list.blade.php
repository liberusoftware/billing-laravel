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
    <form wire:submit="adjust"><input type="number" wire:model="selectedInvoiceId" placeholder="{{ __('Invoice ID') }}"><input type="number" wire:model="adjustmentMinor" placeholder="{{ __('Signed adjustment') }}"><input wire:model="adjustmentReason" placeholder="{{ __('Reason') }}"><button type="submit">{{ __('Apply adjustment') }}</button></form>
    <label>{{ __('Delivery email') }} <input type="email" wire:model="deliveryDestination"></label>
    <ul>@foreach ($invoices as $invoice)<li>{{ $invoice->id }} — {{ $invoice->status->value }} — {{ $invoice->total_minor }} {{ $invoice->currency }} <button type="button" wire:click="finalize({{ $invoice->id }})">{{ __('Finalize') }}</button><button type="button" wire:click="document({{ $invoice->id }})">{{ __('Generate PDF') }}</button><button type="button" wire:click="deliver({{ $invoice->id }})">{{ __('Deliver') }}</button></li>@endforeach</ul>
</div>
