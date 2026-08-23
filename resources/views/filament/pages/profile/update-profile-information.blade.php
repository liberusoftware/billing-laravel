<x-filament-panels::page>
    @if (session('status'))
        <div class="text-sm text-success-600 dark:text-success-400">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="submit" class="space-y-6">
        {{ $this->form }}

        <div>
            <x-filament::button type="submit">
                Save
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
