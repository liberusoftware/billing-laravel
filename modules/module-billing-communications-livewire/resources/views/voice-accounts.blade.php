<div>
    <form wire:submit="create">
        <input type="number" wire:model="customerId" placeholder="{{ __('Customer ID') }}">
        <input type="text" wire:model="platform" placeholder="{{ __('Platform') }}">
        <input type="text" wire:model="sipUsername" placeholder="{{ __('SIP username') }}">
        <input type="password" wire:model="sipSecret" placeholder="{{ __('SIP secret') }}">
        <button type="submit">{{ __('Create voice account') }}</button>
    </form>
    @if (session('billing-communications-message')) <p role="status">{{ session('billing-communications-message') }}</p> @endif
    <ul wire:loading.class="opacity-50">
        @forelse ($accounts as $account)
            <li wire:key="voice-account-{{ $account->id }}">{{ $account->sip_username }} ({{ $account->status }}) <button type="button" wire:click="provision({{ $account->id }})">{{ __('Provision') }}</button></li>
        @empty
            <li>{{ __('No voice accounts found.') }}</li>
        @endforelse
    </ul>
</div>
