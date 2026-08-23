

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Report') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <form method="POST" action="{{ route('reports.store') }}">
                    @csrf
                    
                    <div class="mb-4">
                        <x-label for="name" value="{{ __('Report Name') }}" />
                        <x-input id="name" type="text" name="name" class="block mt-1 w-full" required />
                    </div>

                    <div class="mb-4">
                        <x-label for="type" value="{{ __('Report Type') }}" />
                        <select name="type" id="type" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            <option value="billing_summary">Billing Summary</option>
                            <option value="revenue_report">Revenue Report</option>
                            <option value="customer_activity">Customer Activity</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <x-label for="format" value="{{ __('Format') }}" />
                        <select name="format" id="format" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            <option value="pdf">PDF</option>
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <x-label for="schedule" value="{{ __('Schedule (Optional)') }}" />
                        <select name="schedule[frequency]" id="schedule" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">No Schedule</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <x-button class="ml-4">
                            {{ __('Create Report') }}
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>