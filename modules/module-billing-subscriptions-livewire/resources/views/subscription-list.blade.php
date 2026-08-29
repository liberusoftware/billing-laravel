<section aria-labelledby="module-billing-subscriptions-heading">
    <h2 id="module-billing-subscriptions-heading">{{ __('Subscriptions') }}</h2>
    @if (session()->has('module-billing-subscriptions-message'))
        <p role="status">{{ session('module-billing-subscriptions-message') }}</p>
    @endif
    <button type="button" wire:click="expireDue">{{ __('Expire due subscriptions') }}</button>
    <button type="button" wire:click="$set('showActivate', true)">{{ __('Activate subscription') }}</button>
    @if ($showActivate)
        <form wire:submit="activate">
            <label>{{ __('Pricing plan ID') }} <input type="number" min="0" wire:model="pricingPlanId"></label>
            <label>{{ __('Trial days') }} <input type="number" min="0" max="365" wire:model="trialDays"></label>
            <button type="submit">{{ __('Activate') }}</button>
        </form>
    @endif
    <ul>
        @forelse ($subscriptions as $subscription)
            <li>
                {{ $subscription->id }} — {{ $subscription->status->value }}
                @if ($subscription->status->value === 'paused')
                    <button type="button" wire:click="resume({{ $subscription->id }})">{{ __('Resume') }}</button>
                @elseif ($subscription->status->value !== 'cancelled' && $subscription->status->value !== 'expired')
                    <button type="button" wire:click="renew({{ $subscription->id }})">{{ __('Renew') }}</button>
                    <button type="button" wire:click="pause({{ $subscription->id }})">{{ __('Pause') }}</button>
                    <button type="button" wire:click="cancel({{ $subscription->id }})">{{ __('Cancel') }}</button>
                @endif
                @if ($subscription->status->value !== 'cancelled' && $subscription->status->value !== 'expired')
                    <button type="button" wire:click="changePlan({{ $subscription->id }})">{{ __('Change plan') }}</button>
                @endif
            </li>
        @empty
            <li>{{ __('No subscriptions found.') }}</li>
        @endforelse
    </ul>
</section>
