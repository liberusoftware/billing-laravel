

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold">Analytics Dashboard</h2>
        <button wire:click="$refresh" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
            Refresh Data
        </button>
    </div>

    <div class="mb-6">
        <h3 class="text-lg font-medium mb-2">Customize Dashboard</h3>
        <div class="flex gap-4">
            @foreach($availableCharts as $key => $chart)
                <label class="flex items-center">
                    <input type="checkbox" 
                           wire:model="chartPreferences.{{ $key }}"
                           wire:change="toggleChart('{{ $key }}')"
                           class="mr-2">
                    {{ $chart['title'] }}
                </label>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($activeCharts as $chartKey)
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium mb-4">{{ $availableCharts[$chartKey]['title'] }}</h3>
                <div wire:ignore>
                    <div id="chart-{{ $chartKey }}" class="w-full"></div>
                </div>
            </div>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', function () {
        const charts = {};
        const metrics = @json($metrics);
        
        function initializeCharts() {
            @foreach($activeCharts as $chartKey)
                const options{{ $chartKey }} = {
                    chart: {
                        type: '{{ $availableCharts[$chartKey]['type'] }}',
                        height: 350
                    },
                    series: @json($metrics[$chartKey]['series']),
                    labels: @json($metrics[$chartKey]['labels']),
                    theme: {
                        palette: 'palette1'
                    }
                };

                if (charts['{{ $chartKey }}']) {
                    charts['{{ $chartKey }}'].destroy();
                }

                charts['{{ $chartKey }}'] = new ApexCharts(
                    document.querySelector("#chart-{{ $chartKey }}"),
                    options{{ $chartKey }}
                );
                charts['{{ $chartKey }}'].render();
            @endforeach
        }

        initializeCharts();

        Livewire.on('refreshCharts', () => {
            initializeCharts();
        });
    });
</script>
@endpush