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
    <ul wire:loading.class="opacity-50">
        <li wire:loading>{{ __('Loading…') }}</li>
        @forelse ($accounts as $account)
            <li wire:key="hosting-account-{{ $account->id }}">
                {{ $account->name }} ({{ $account->status }})
                <button type="button" wire:click="$set('selectedAccountId', {{ $account->id }})">{{ __('Select') }}</button>
                <button type="button" wire:click="transitionAccount">{{ __('Update status') }}</button>
            </li>
        @empty
            <li>{{ __('No hosting accounts found.') }}</li>
        @endforelse
    </ul>
</div>
