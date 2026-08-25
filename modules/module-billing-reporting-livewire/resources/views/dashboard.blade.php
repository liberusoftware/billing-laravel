<section wire:loading.class="opacity-50" aria-labelledby="billing-reporting-dashboard-heading">
    <h2 id="billing-reporting-dashboard-heading">{{ __('Billing reporting') }}</h2>
    <label>{{ __('Metric') }} <select wire:model.live="metric"><option value="">{{ __('All metrics') }}</option>@foreach (['mrr', 'arr', 'churn', 'aging', 'revenue', 'tax', 'usage', 'provisioning', 'collection', 'provider'] as $type)<option value="{{ $type }}">{{ strtoupper($type) }}</option>@endforeach</select></label>
    <form wire:submit="calculate">
        <input type="date" wire:model="periodStart" aria-label="{{ __('Period start') }}">
        <input type="date" wire:model="periodEnd" aria-label="{{ __('Period end') }}">
        <input type="text" wire:model="currency" maxlength="3" aria-label="{{ __('Currency') }}">
        <button type="submit">{{ __('Calculate selected metric') }}</button>
    </form>
    @if (session('module-billing-reporting-message'))<p>{{ session('module-billing-reporting-message') }}</p>@endif
    <ul>
        @forelse ($metrics as $reportingMetric)
            <li wire:key="reporting-dashboard-metric-{{ $reportingMetric->id }}">{{ strtoupper($reportingMetric->metric->value) }}: {{ $reportingMetric->value }} {{ $reportingMetric->currency }} ({{ $reportingMetric->period_start?->toDateString() }} – {{ $reportingMetric->period_end?->toDateString() }})</li>
        @empty
            <li>{{ __('No reporting metrics found.') }}</li>
        @endforelse
    </ul>
    {{ $metrics->links() }}
</section>
