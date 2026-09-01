<div>
    <div class="billing-page-heading">
        <div><h1>{{ __('Good morning, :name', ['name' => auth()->user()?->name ?? __('there')]) }}</h1><p>{{ __('Here is what is happening across your billing workspace.') }}</p></div>
        <a class="billing-action" href="{{ url('/invoices/create') }}">{{ __('Create invoice') }}</a>
    </div>

    <div class="billing-metric-grid" aria-label="{{ __('Billing summary') }}">
        <article class="billing-panel billing-metric"><small>{{ __('Collected revenue') }}</small><strong>{{ number_format((float) ($metrics['revenue']['series'][0]['data']->sum() ?? 0), 2) }}</strong><span>↗ {{ __('12.4% this month') }}</span></article>
        <article class="billing-panel billing-metric"><small>{{ __('Open invoices') }}</small><strong>{{ count($metrics['invoices']['labels'] ?? []) }}</strong><span>↗ {{ __('Healthy cash flow') }}</span></article>
        <article class="billing-panel billing-metric"><small>{{ __('Active customers') }}</small><strong>{{ count($metrics['clients']['labels'] ?? []) }}</strong><span>↗ {{ __('Growing steadily') }}</span></article>
        <article class="billing-panel billing-metric"><small>{{ __('Collection rate') }}</small><strong>94.8%</strong><span>{{ __('Above target') }}</span></article>
    </div>

    <div class="billing-dashboard-grid">
        <section class="billing-panel">
            <div class="billing-panel-header"><div><h2>{{ __('Revenue overview') }}</h2><p class="billing-breadcrumb">{{ __('Your billing performance over time') }}</p></div><button type="button" class="billing-action" wire:click="$refresh" wire:loading.attr="disabled">{{ __('Refresh') }}</button></div>
            <div class="billing-panel-body"><div class="billing-chart" wire:loading.class="opacity-50" wire:target="$refresh"><div wire:ignore><div id="chart-revenue" aria-label="{{ __('Revenue chart') }}"></div></div></div></div>
        </section>
        <section class="billing-panel">
            <div class="billing-panel-header"><h2>{{ __('Invoice health') }}</h2><span class="billing-status">{{ __('On track') }}</span></div>
            <div class="billing-panel-body"><ul class="billing-list">@forelse(($metrics['invoices']['labels'] ?? []) as $index => $status)<li wire:key="billing-invoice-status-{{ $index }}"><span>{{ str_replace('_', ' ', ucfirst($status)) }}</span><strong>{{ $metrics['invoices']['series'][$index] ?? 0 }}</strong></li>@empty<li>{{ __('No invoice activity yet.') }}</li>@endforelse</ul></div>
        </section>
    </div>

    <section class="billing-panel" style="margin-block-start: 1.25rem"><div class="billing-panel-header"><div><h2>{{ __('Customize your overview') }}</h2><p class="billing-breadcrumb">{{ __('Choose the signals you want to see first.') }}</p></div></div><div class="billing-panel-body"><div class="billing-filter">@foreach($availableCharts as $key => $chart)<label wire:key="billing-chart-toggle-{{ $key }}"><input type="checkbox" wire:model="chartPreferences.{{ $key }}" wire:change="toggleChart('{{ $key }}')"> {{ __($chart['title']) }}</label>@endforeach</div></div></section>
</div>

@push('scripts')
<script>
document.addEventListener('livewire:initialized', () => {
    const metrics = @json($metrics);
    const target = document.querySelector('#chart-revenue');
    if (target && window.ApexCharts) {
        new ApexCharts(target, {chart: {type: 'area', height: 250, toolbar: {show: false}}, series: metrics.revenue.series, xaxis: {categories: metrics.revenue.labels}, colors: ['#4f46e5'], stroke: {curve: 'smooth', width: 3}, fill: {type: 'gradient', gradient: {opacityFrom: .28, opacityTo: .02}}, dataLabels: {enabled: false}}).render();
    }
});
</script>
@endpush
