<x-filament-panels::page>
    <div class="space-y-4">
        @forelse ($this->announcements as $announcement)
            <x-filament::section>
                <x-slot name="heading">
                    {{ $announcement->title }}
                </x-slot>

                <x-slot name="description">
                    {{ $announcement->type === 'network_status' ? 'Network status' : 'Announcement' }}
                </x-slot>

                <div class="prose dark:prose-invert max-w-none">
                    {{ $announcement->body }}
                </div>
            </x-filament::section>
        @empty
            <p class="text-gray-500">No announcements at this time.</p>
        @endforelse
    </div>
</x-filament-panels::page>
