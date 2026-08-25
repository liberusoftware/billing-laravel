<section aria-labelledby="billing-accounts-heading">
    <h2 id="billing-accounts-heading">{{ __('Billing accounts') }}</h2>

    @if (session()->has('billing-core-message'))
        <p role="status">{{ session('billing-core-message') }}</p>
    @endif

    <button type="button" wire:click="$set('showCreate', true)">{{ __('Create account') }}</button>

    @if ($showCreate)
        <form wire:submit="save">
            <label>{{ __('Name') }} <input wire:model="name" required /></label>
            @error('name') <p role="alert">{{ $message }}</p> @enderror
            <label>{{ __('Currency') }} <input wire:model="currency" maxlength="3" required /></label>
            @error('currency') <p role="alert">{{ $message }}</p> @enderror
            <button type="submit">{{ __('Save') }}</button>
            <button type="button" wire:click="$set('showCreate', false)">{{ __('Cancel') }}</button>
        </form>
    @endif

    <ul>
        @forelse ($accounts as $account)
            <li wire:key="billing-account-{{ $account->id }}">
                {{ $account->name }} — {{ $account->currency }} — {{ $account->status->value }}
                <button type="button" wire:click="$set('selectedAccountId', {{ $account->id }})">{{ __('Select') }}</button>
            </li>
        @empty
            <li>{{ __('No billing accounts found.') }}</li>
        @endforelse
    </ul>

    @if ($selectedAccountId)
        <form wire:submit="updateAccount">
            <label>{{ __('Name') }} <input wire:model="name" required /></label>
            <label>{{ __('Currency') }} <input wire:model="currency" maxlength="3" required /></label>
            <button type="submit">{{ __('Update account') }}</button>
        </form>
        <form wire:submit="transitionAccount">
            <label>{{ __('Status') }} <select wire:model="status"><option value="active">{{ __('Active') }}</option><option value="suspended">{{ __('Suspended') }}</option><option value="closed">{{ __('Closed') }}</option></select></label>
            <button type="submit">{{ __('Update status') }}</button>
        </form>
    @endif
</section>
