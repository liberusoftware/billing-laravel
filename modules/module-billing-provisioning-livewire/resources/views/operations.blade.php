<section aria-labelledby="module-billing-provisioning-heading" wire:loading.class="opacity-50">
    <h2 id="module-billing-provisioning-heading">{{ __('Provisioning') }}</h2>
    @if (session()->has('module-billing-provisioning-message'))
        <p role="status">{{ session('module-billing-provisioning-message') }}</p>
    @endif
    <form wire:submit="createService">
        <label>{{ __('Provider') }} <input wire:model="provider" maxlength="100" required></label>
        <label>{{ __('External ID') }} <input wire:model="externalId" maxlength="255"></label>
        <button type="submit">{{ __('Create service') }}</button>
    </form>
    <form wire:submit="queue">
        <label>{{ __('Service') }}
            <select wire:model="selectedServiceId" required>
                <option value="">{{ __('Select a service') }}</option>
                @foreach ($services as $service)
                    <option value="{{ $service->id }}">{{ $service->provider }} — {{ $service->state->value }}</option>
                @endforeach
            </select>
        </label>
        <label>{{ __('Operation') }}
            <select wire:model="operation">
                @foreach (['provision', 'deprovision', 'poll', 'reconcile', 'rollback'] as $availableOperation)
                    <option value="{{ $availableOperation }}">{{ ucfirst($availableOperation) }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit">{{ __('Queue operation') }}</button>
        <button type="button" wire:click="reconcile">{{ __('Reconcile service') }}</button>
        <label>{{ __('State') }} <select wire:model="state">@foreach (['pending', 'provisioning', 'active', 'suspended', 'failed', 'deprovisioning', 'deprovisioned'] as $availableState)<option value="{{ $availableState }}">{{ ucfirst($availableState) }}</option>@endforeach</select></label>
        <label>{{ __('Error') }} <input wire:model="lastError" maxlength="2000"></label>
        <button type="button" wire:click="transitionService">{{ __('Transition service') }}</button>
    </form>
    <ul>
        @forelse ($operations as $operation)
            <li wire:key="provisioning-operation-{{ $operation->id }}">{{ $operation->operation }} ({{ $operation->status }}) <button type="button" wire:click="$set('selectedOperationId', {{ $operation->id }})">{{ __('Select') }}</button></li>
        @empty
            <li>{{ __('No provisioning operations found.') }}</li>
        @endforelse
    </ul>
    @if ($selectedOperationId)<button type="button" wire:click="run">{{ __('Run selected operation') }}</button>@endif
</section>
