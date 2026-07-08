<x-filament-panels::page>
    <x-filament-widgets::widgets
        :widgets="$this->getHeaderWidgets()"
        :columns="$this->getHeaderWidgetsColumns()"
    />

    <div class="grid gap-6 md:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Time worked per project</x-slot>

            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500">
                        <th class="pb-2">Project</th>
                        <th class="pb-2 text-right">Hours</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($perProject as $row)
                        <tr class="border-t border-gray-100 dark:border-gray-700">
                            <td class="py-2">{{ $row['name'] }}</td>
                            <td class="py-2 text-right">{{ number_format($row['hours'], 1) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="py-2 text-gray-400">No time logged yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Time worked per staff member</x-slot>

            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500">
                        <th class="pb-2">Staff</th>
                        <th class="pb-2 text-right">Hours</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($perStaff as $row)
                        <tr class="border-t border-gray-100 dark:border-gray-700">
                            <td class="py-2">{{ $row['name'] }}</td>
                            <td class="py-2 text-right">{{ number_format($row['hours'], 1) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="py-2 text-gray-400">No time logged yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-filament::section>
    </div>
</x-filament-panels::page>
