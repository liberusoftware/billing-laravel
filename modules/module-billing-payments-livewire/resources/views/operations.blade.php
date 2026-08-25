<section aria-labelledby="module-billing-payments-operations-heading" wire:loading.class="opacity-50">
    <h2 id="module-billing-payments-operations-heading">{{ __('Payment operations') }}</h2>
    @if (session()->has('module-billing-payments-operations-message'))
        <p role="status">{{ session('module-billing-payments-operations-message') }}</p>
    @endif
    <form>
        <label>{{ __('Payment') }}
            <select wire:model="selectedPaymentId" required>
                <option value="">{{ __('Select a payment') }}</option>
                @foreach ($payments as $payment)
                    <option value="{{ $payment->id }}">{{ $payment->amount_minor }} {{ $payment->currency }} — {{ $payment->status->value }}</option>
                @endforeach
            </select>
        </label>
        <label>{{ __('Amount in minor units') }} <input type="number" min="1" wire:model="amountMinor"></label>
        <label>{{ __('Reason') }} <input wire:model="reason" maxlength="255"></label>
        <button type="button" wire:click="refund">{{ __('Refund') }}</button>
        <button type="button" wire:click="dispute">{{ __('Open dispute') }}</button>
        <button type="button" wire:click="capture">{{ __('Capture') }}</button>
        <label>{{ __('Invoice ID') }} <input type="number" min="1" wire:model="invoiceId"></label>
        <button type="button" wire:click="allocate">{{ __('Allocate') }}</button>
        <label>{{ __('Provider reference') }} <input wire:model="providerReference" maxlength="255"></label>
        <button type="button" wire:click="reconcile">{{ __('Reconcile') }}</button>
    </form>
    <ul>
        @forelse ($disputes as $dispute)
            <li wire:key="payment-dispute-{{ $dispute->id }}">{{ __('Dispute') }} {{ $dispute->payment_id }} ({{ $dispute->status->value }})</li>
        @empty
            <li>{{ __('No disputes found.') }}</li>
        @endforelse
    </ul>
    <ul>
        @forelse ($reconciliations as $reconciliation)
            <li wire:key="payment-reconciliation-{{ $reconciliation->id }}">{{ __('Reconciliation') }} {{ $reconciliation->provider_reference }} ({{ $reconciliation->status->value }})</li>
        @empty
            <li>{{ __('No reconciliations found.') }}</li>
        @endforelse
    </ul>
</section>
