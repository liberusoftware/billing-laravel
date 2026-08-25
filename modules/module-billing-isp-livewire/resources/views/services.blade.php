<div>
    <form wire:submit="createService">
        <input type="text" wire:model="name" placeholder="{{ __('Name') }}">
        <button type="submit">{{ __('Create') }}</button>
    </form>
    @if (session('isp-services-message'))<p role="status">{{ session('isp-services-message') }}</p>@endif
    <label>{{ __('Status') }} <select wire:model="status"><option value="pending">{{ __('Pending') }}</option><option value="active">{{ __('Active') }}</option><option value="suspended">{{ __('Suspended') }}</option><option value="cancelled">{{ __('Cancelled') }}</option><option value="failed">{{ __('Failed') }}</option></select></label>
    <ul wire:loading.class="opacity-50">
        <li wire:loading>{{ __('Loading…') }}</li>
        @forelse ($services as $service)
            <li wire:key="isp-service-{{ $service->id }}">{{ $service->name }} ({{ $service->status }}) <button type="button" wire:click="$set('selectedServiceId', {{ $service->id }})">{{ __('Select') }}</button> <button type="button" wire:click="transition">{{ __('Update status') }}</button></li>
        @empty
            <li>{{ __('No ISP access services found.') }}</li>
        @endforelse
    </ul>
</div>
