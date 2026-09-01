<section aria-labelledby="billing-currency-converter-heading">
    <h2 id="billing-currency-converter-heading">{{ __('Currency converter') }}</h2>
    <form wire:submit="convert">
        <input wire:model="amount" type="number" step="any" required aria-label="{{ __('Amount') }}">
        <input wire:model="from" maxlength="3" required aria-label="{{ __('From currency') }}">
        <input wire:model="to" maxlength="3" required aria-label="{{ __('To currency') }}">
        <button type="submit">{{ __('Convert') }}</button>
    </form>
    @if ($result)
        <p role="status">{{ $result['amount'] }} {{ $result['to'] }} ({{ __('rate') }}: {{ $result['rate'] }})</p>
    @endif
</section>
