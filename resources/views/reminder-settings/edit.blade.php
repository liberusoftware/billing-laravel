

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Reminder Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('reminder-settings.update') }}">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <div>
                        <x-label for="days_before_reminder" value="Days Before First Reminder" />
                        <x-input id="days_before_reminder" name="days_before_reminder" type="number" 
                                class="mt-1 block w-full" 
                                :value="old('days_before_reminder', $settings->days_before_reminder)" required />
                    </div>

                    <div>
                        <x-label for="reminder_frequency" value="Days Between Reminders" />
                        <x-input id="reminder_frequency" name="reminder_frequency" type="number" 
                                class="mt-1 block w-full" 
                                :value="old('reminder_frequency', $settings->reminder_frequency)" required />
                    </div>

                    <div>
                        <x-label for="max_reminders" value="Maximum Number of Reminders" />
                        <x-input id="max_reminders" name="max_reminders" type="number" 
                                class="mt-1 block w-full" 
                                :value="old('max_reminders', $settings->max_reminders)" required />
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" class="form-checkbox" 
                                   {{ $settings->is_active ? 'checked' : '' }}>
                            <span class="ml-2">Enable Automatic Reminders</span>
                        </label>
                    </div>

                    <div class="flex justify-end">
                        <x-button>
                            {{ __('Save Settings') }}
                        </x-button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>