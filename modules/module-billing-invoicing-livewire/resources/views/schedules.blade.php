<div>
    <form wire:submit="createSchedule">
        <label>{{ __('Frequency') }} <select wire:model="frequency"><option value="daily">{{ __('Daily') }}</option><option value="weekly">{{ __('Weekly') }}</option><option value="monthly">{{ __('Monthly') }}</option><option value="yearly">{{ __('Yearly') }}</option></select></label>
        <label>{{ __('Next run') }} <input type="datetime-local" wire:model="nextRunAt"></label>
        <label><input type="checkbox" wire:model="active"> {{ __('Active') }}</label>
        <button type="submit">{{ __('Create schedule') }}</button>
    </form>
    @if (session('module-billing-invoicing-schedules-message'))<p role="status">{{ session('module-billing-invoicing-schedules-message') }}</p>@endif
    <ul wire:loading.class="opacity-50">
        <li wire:loading>{{ __('Loading…') }}</li>
        @forelse ($schedules as $schedule)
            <li wire:key="invoice-schedule-{{ $schedule->id }}">{{ $schedule->frequency }} — {{ $schedule->next_run_at }} <button type="button" wire:click="$set('selectedScheduleId', {{ $schedule->id }})">{{ __('Select') }}</button><button type="button" wire:click="runSchedule">{{ __('Run') }}</button></li>
        @empty<li>{{ __('No invoice schedules found.') }}</li>@endforelse
    </ul>
</div>
