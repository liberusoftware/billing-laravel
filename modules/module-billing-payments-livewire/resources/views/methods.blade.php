<section aria-labelledby="module-billing-payments-methods-heading">
    <h2 id="module-billing-payments-methods-heading">{{ __('Payment methods') }}</h2>
    @if (session('module-billing-payments-methods-message'))<p role="status">{{ session('module-billing-payments-methods-message') }}</p>@endif
    <form wire:submit="createMethod">
        <label>{{ __('Type') }} <input wire:model="type" type="text" required></label>
        <label>{{ __('Provider') }} <input wire:model="provider" type="text" required></label>
        <label>{{ __('Customer ID') }} <input wire:model="customerId" type="number" min="0"></label>
        <label>{{ __('Display name') }} <input wire:model="displayName" type="text"></label>
        <label>{{ __('Last four') }} <input wire:model="lastFour" type="text" maxlength="4"></label>
        <label><input wire:model="isDefault" type="checkbox"> {{ __('Default method') }}</label>
        <button type="submit">{{ __('Create method') }}</button>
    </form>
    <ul>
        @forelse ($methods as $method)
            <li wire:key="payment-method-{{ $method->id }}">{{ $method->display_name ?: $method->type }} — {{ $method->provider }} @if ($method->last_four)•••• {{ $method->last_four }}@endif <button type="button" wire:click="$set('selectedPaymentMethodId', {{ $method->id }})">{{ __('Select') }}</button></li>
        @empty
            <li>{{ __('No payment methods found.') }}</li>
        @endforelse
    </ul>
    <form wire:submit="createMandate">
        <h3>{{ __('Create mandate') }}</h3>
        <label>{{ __('Provider') }} <input wire:model="mandateProvider" type="text" required></label>
        <label>{{ __('Provider reference') }} <input wire:model="mandateProviderReference" type="text"></label>
        <label>{{ __('Status') }} <input wire:model="mandateStatus" type="text" required></label>
        <button type="submit">{{ __('Create mandate') }}</button>
    </form>
    <ul>
        @forelse ($mandates as $mandate)
            <li wire:key="payment-mandate-{{ $mandate->id }}">{{ $mandate->provider }} — {{ $mandate->status }}</li>
        @empty
            <li>{{ __('No payment mandates found.') }}</li>
        @endforelse
    </ul>
</section>
