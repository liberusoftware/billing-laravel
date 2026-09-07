<x-filament-panels::page>
    <form wire:submit="placeOrder" class="space-y-6">
        {{ $this->form }}

        @if ($total = $this->orderTotal())
            <div class="space-y-2">
                <p>Initial total: {{ $total }}</p>
                @if ($billingCycle !== 'one-time')
                    <p>Renewal total: {{ $total }} ({{ $billingCycle }})</p>
                @endif
                @if ($this->requiresDomain())
                    <p>Hosting will be activated after payment is confirmed.</p>
                @endif
            </div>
        @endif

        <x-filament::button type="submit">
            Place order
        </x-filament::button>
    </form>
</x-filament-panels::page>
