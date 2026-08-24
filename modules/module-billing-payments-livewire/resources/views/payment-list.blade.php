<section aria-labelledby="module-billing-payments-heading">
    <h2 id="module-billing-payments-heading">{{ __('Payments') }}</h2>
    @if (session()->has('module-billing-payments-message'))
        <p role="status">{{ session('module-billing-payments-message') }}</p>
    @endif
    <button type="button" wire:click="$set('showCreate', true)">{{ __('Create payment') }}</button>
    @if ($showCreate)
        <form wire:submit="createPayment">
            <label>{{ __('Amount in minor units') }} <input type="number" min="1" wire:model="amountMinor" required></label>
            <label>{{ __('Currency') }} <input wire:model="currency" maxlength="3" required></label>
            <button type="submit">{{ __('Create') }}</button>
        </form>
    @endif
    <ul>
        @forelse ($payments as $payment)
            <li>{{ $payment->amount_minor }} {{ $payment->currency }} — {{ $payment->status->value }}</li>
        @empty
            <li>{{ __('No payments found.') }}</li>
        @endforelse
    </ul>
</section>
