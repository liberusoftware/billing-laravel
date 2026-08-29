<section aria-labelledby="billing-tax-calculator-heading">
    <h2 id="billing-tax-calculator-heading">{{ __('Tax calculator') }}</h2>
    <form wire:submit="calculate">
        <input wire:model="amount" type="number" step="any" min="0" required aria-label="{{ __('Amount') }}">
        <input wire:model="jurisdiction" placeholder="{{ __('Jurisdiction') }}" aria-label="{{ __('Jurisdiction') }}">
        <button type="submit">{{ __('Calculate') }}</button>
    </form>
    @if ($result)
        <p role="status">{{ __('Tax') }}: {{ $result['tax'] }}; {{ __('Total') }}: {{ $result['total'] }}</p>
    @endif
</section>
