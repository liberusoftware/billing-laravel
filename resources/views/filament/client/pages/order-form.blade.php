<x-filament-panels::page>
    <form wire:submit="placeOrder" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit">
            Place order
        </x-filament::button>
    </form>
</x-filament-panels::page>
