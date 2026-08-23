<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public array $activeCharts = [];

    public ?array $chartPreferences = null;

    protected array $availableCharts = [
        'revenue' => [
            'title' => 'Revenue Overview',
            'type' => 'line',
        ],
        'invoices' => [
            'title' => 'Invoice Status',
            'type' => 'pie',
        ],
        'clients' => [
            'title' => 'Active Clients',
            'type' => 'bar',
        ],
    ];

    public function mount(): void
    {
        $this->chartPreferences = auth()->user()->dashboard_preferences
            ?? array_fill_keys(
                array_keys($this->availableCharts),
                true
            );

        $this->activeCharts = array_keys(array_filter($this->chartPreferences));
    }

    public function toggleChart(string $chartKey): void
    {
        $this->chartPreferences[$chartKey] = ! ($this->chartPreferences[$chartKey] ?? false);
        auth()->user()->update(['dashboard_preferences' => $this->chartPreferences]);
        $this->activeCharts = array_keys(array_filter($this->chartPreferences));
    }

    public function getMetrics(): array
    {
        return Cache::remember(
            'dashboard.metrics',
            300,
            fn (): array => [
                'revenue' => $this->getRevenueData(),
                'invoices' => $this->getInvoiceData(),
                'clients' => $this->getClientData(),
            ]
        );
    }

    private function getRevenueData(): array
    {
        $revenue = Invoice::where(
            'status',
            'paid'
        )
            ->selectRaw('DATE(paid_at) as date, SUM(total_amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $revenue->pluck('date'),
            'series' => [
                [
                    'name' => 'Revenue',
                    'data' => $revenue->pluck('total'),
                ],
            ],
        ];
    }

    private function getInvoiceData(): array
    {
        $statuses = Invoice::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        return [
            'labels' => $statuses->pluck('status'),
            'series' => $statuses->pluck('count'),
        ];
    }

    private function getClientData(): array
    {
        $clients = Client::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $clients->pluck('date'),
            'series' => [
                [
                    'name' => 'New Clients',
                    'data' => $clients->pluck('count'),
                ],
            ],
        ];
    }

    public function render(): View
    {
        return view(
            'livewire.dashboard',
            [
                'metrics' => $this->getMetrics(),
                'availableCharts' => $this->availableCharts,
            ]
        );
    }
}
