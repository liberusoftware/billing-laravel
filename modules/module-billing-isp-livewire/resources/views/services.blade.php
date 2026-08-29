<div>
    <form wire:submit="createService">
        <input type="text" wire:model="name" placeholder="{{ __('Name') }}">
        <input type="number" wire:model="monthlyDataLimitBytes" min="0" placeholder="{{ __('Monthly byte limit') }}">
        <button type="submit">{{ __('Create') }}</button>
    </form>
    @if (session('isp-services-message'))<p role="status">{{ session('isp-services-message') }}</p>@endif
    <label>{{ __('Status') }} <select wire:model="status"><option value="pending">{{ __('Pending') }}</option><option value="active">{{ __('Active') }}</option><option value="suspended">{{ __('Suspended') }}</option><option value="cancelled">{{ __('Cancelled') }}</option><option value="failed">{{ __('Failed') }}</option></select></label>
    <ul wire:loading.class="opacity-50">
        <li wire:loading>{{ __('Loading…') }}</li>
        @forelse ($services as $service)
            <li wire:key="isp-service-{{ $service->id }}">{{ $service->name }} ({{ $service->status }}) — {{ $service->current_period_usage_bytes }} bytes <button type="button" wire:click="$set('selectedServiceId', {{ $service->id }})">{{ __('Select') }}</button> <button type="button" wire:click="transitionService">{{ __('Update status') }}</button> <button type="button" wire:click="resetUsage">{{ __('Reset usage') }}</button></li>
        @empty
            <li>{{ __('No ISP access services found.') }}</li>
        @endforelse
    </ul>
    <input wire:model="adapter" placeholder="{{ __('Network adapter') }}"><button type="button" wire:click="synchronize">{{ __('Synchronize') }}</button>
    <input wire:model="accountingSessionId" placeholder="{{ __('Accounting session ID') }}"><input wire:model="accountingStartedAt" type="datetime-local"><input wire:model="inputBytes" type="number" min="0"><input wire:model="outputBytes" type="number" min="0"><button type="button" wire:click="recordAccounting">{{ __('Record accounting') }}</button>
</div>
