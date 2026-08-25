<section wire:loading.class="opacity-50" aria-labelledby="billing-reporting-dashboard-heading">
    <h2 id="billing-reporting-dashboard-heading">{{ __('Billing reporting') }}</h2>
    <label>{{ __('Metric') }} <select wire:model.live="metric"><option value="">{{ __('All metrics') }}</option>@foreach (['mrr', 'arr', 'churn', 'aging', 'revenue', 'tax', 'usage', 'provisioning', 'collection', 'provider'] as $type)<option value="{{ $type }}">{{ strtoupper($type) }}</option>@endforeach</select></label>
    <ul>
        @forelse ($metrics as $reportingMetric)
            <li wire:key="reporting-dashboard-metric-{{ $reportingMetric->id }}">{{ strtoupper($reportingMetric->metric->value) }}: {{ $reportingMetric->value }} {{ $reportingMetric->currency }} ({{ $reportingMetric->period_start?->toDateString() }} – {{ $reportingMetric->period_end?->toDateString() }})</li>
        @empty
            <li>{{ __('No reporting metrics found.') }}</li>
        @endforelse
    </ul>
    {{ $metrics->links() }}
</section>
