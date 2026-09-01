<section aria-labelledby="billing-orders-quotes-heading">
    <h2 id="billing-orders-quotes-heading">{{ __('Quotes') }}</h2>
    @if (session('billing-orders-quotes-message'))<p role="status">{{ session('billing-orders-quotes-message') }}</p>@endif
    <label>{{ __('Status') }} <select wire:model="status"><option value="sent">{{ __('Sent') }}</option><option value="viewed">{{ __('Viewed') }}</option><option value="accepted">{{ __('Accepted') }}</option><option value="declined">{{ __('Declined') }}</option></select></label>
    <ul wire:loading.class="opacity-50">
        @forelse ($quotes as $quote)
            <li wire:key="billing-quote-{{ $quote->id }}">{{ $quote->quote_number }} — {{ $quote->total_minor }} {{ $quote->currency }} ({{ $quote->status }}) <button type="button" wire:click="$set('selectedQuoteId', {{ $quote->id }})">{{ __('Select') }}</button> <button type="button" wire:click="transition">{{ __('Update status') }}</button> <button type="button" wire:click="convert">{{ __('Convert') }}</button></li>
        @empty
            <li>{{ __('No quotes found.') }}</li>
        @endforelse
    </ul>
</section>
