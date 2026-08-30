<div>
    <form wire:submit="createAccount">
        <label>{{ __('Name') }} <input type="text" wire:model="name" required></label>
        <label>{{ __('Initial status') }} <select wire:model="status"><option value="pending">{{ __('Pending') }}</option><option value="active">{{ __('Active') }}</option><option value="suspended">{{ __('Suspended') }}</option><option value="cancelled">{{ __('Cancelled') }}</option><option value="failed">{{ __('Failed') }}</option></select></label>
        <button type="submit">{{ __('Create account') }}</button>
    </form>

    @if (session('hosting-accounts-message'))
        <p role="status">{{ session('hosting-accounts-message') }}</p>
    @endif

    <label>{{ __('Transition status') }} <select wire:model="status"><option value="pending">{{ __('Pending') }}</option><option value="active">{{ __('Active') }}</option><option value="suspended">{{ __('Suspended') }}</option><option value="cancelled">{{ __('Cancelled') }}</option><option value="failed">{{ __('Failed') }}</option></select></label>
    <label>{{ __('Provider operation') }} <select wire:model="operation"><option value="provision">{{ __('Provision') }}</option><option value="suspend">{{ __('Suspend') }}</option><option value="unsuspend">{{ __('Unsuspend') }}</option><option value="change_package">{{ __('Change package') }}</option><option value="add_addon">{{ __('Add addon') }}</option><option value="remove_addon">{{ __('Remove addon') }}</option><option value="terminate">{{ __('Terminate') }}</option></select></label>
    <ul wire:loading.class="opacity-50">
        <li wire:loading>{{ __('Loading…') }}</li>
        @forelse ($accounts as $account)
            <li wire:key="hosting-account-{{ $account->id }}">
                {{ $account->name }} ({{ $account->status }})
                <button type="button" wire:click="$set('selectedAccountId', {{ $account->id }})">{{ __('Select') }}</button>
                <button type="button" wire:click="transitionAccount">{{ __('Update status') }}</button>
                <button type="button" wire:click="performOperation">{{ __('Run provider operation') }}</button>
            </li>
        @empty
            <li>{{ __('No hosting accounts found.') }}</li>
        @endforelse
    </ul>
</div>
