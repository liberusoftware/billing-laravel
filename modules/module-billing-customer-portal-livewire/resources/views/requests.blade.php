<div>
    <form wire:submit="createRequest">
        <label>{{ __('Name') }} <input wire:model="name" maxlength="255" required></label>
        <label>{{ __('Status') }} <select wire:model="status"><option value="active">{{ __('Active') }}</option><option value="closed">{{ __('Closed') }}</option><option value="failed">{{ __('Failed') }}</option></select></label>
        <button type="submit">{{ __('Create request') }}</button>
    </form>
    @if (session('billing-customer-portal-requests-message'))<p role="status">{{ session('billing-customer-portal-requests-message') }}</p>@endif
    <ul wire:loading.class="opacity-50">
        <li wire:loading>{{ __('Loading…') }}</li>
        @forelse ($requests as $request)
            <li wire:key="portal-request-{{ $request->id }}">{{ $request->name }} ({{ $request->status }}) <button type="button" wire:click="$set('selectedRequestId', {{ $request->id }})">{{ __('Select') }}</button><button type="button" wire:click="transitionRequest">{{ __('Update status') }}</button></li>
        @empty<li>{{ __('No portal requests found.') }}</li>@endforelse
    </ul>
</div>
