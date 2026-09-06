<x-filament-panels::page>
    <div class="mb-6 rounded-xl bg-primary-50 p-5 ring-1 ring-primary-200 dark:bg-primary-950/30 dark:ring-primary-800">
        <div class="flex items-start gap-3">
            <x-filament::icon icon="heroicon-o-sparkles" class="mt-0.5 h-6 w-6 text-primary-600" />
            <div>
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Set up your workspace</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">A few defaults now make billing, customer records, and sign-in work smoothly from day one. You can skip optional connections and add them later.</p>
            </div>
        </div>
    </div>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit" icon="heroicon-m-check">
                Save setup
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
