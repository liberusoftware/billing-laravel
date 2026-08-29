<section aria-labelledby="billing-customer-summary-heading" wire:loading.class="opacity-50">
    <h2 id="billing-customer-summary-heading">{{ __('Customer billing summary') }}</h2>
    <form wire:submit="generate">
        <input type="number" min="1" wire:model="customerId" aria-label="{{ __('Customer ID') }}">
        <input type="text" maxlength="3" wire:model="currency" aria-label="{{ __('Currency') }}">
        <button type="submit">{{ __('Generate summary') }}</button>
    </form>
    @if ($summary)
        <dl><dt>{{ __('Invoiced') }}</dt><dd>{{ $summary['total_invoiced'] }}</dd><dt>{{ __('Paid') }}</dt><dd>{{ $summary['total_paid'] }}</dd><dt>{{ __('Outstanding') }}</dt><dd>{{ $summary['total_outstanding'] }}</dd><dt>{{ __('Overdue') }}</dt><dd>{{ $summary['overdue_amount'] }}</dd></dl>
    @endif
</section>
