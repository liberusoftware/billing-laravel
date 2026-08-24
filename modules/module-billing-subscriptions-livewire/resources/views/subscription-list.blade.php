<section aria-labelledby="module-billing-subscriptions-heading">
    <h2 id="module-billing-subscriptions-heading">{{ __('Subscriptions') }}</h2>
    @if (session()->has('module-billing-subscriptions-message'))
        <p role="status">{{ session('module-billing-subscriptions-message') }}</p>
    @endif
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
            <li>{{ $subscription->id }} — {{ $subscription->status->value }}</li>
        @empty
            <li>{{ __('No subscriptions found.') }}</li>
        @endforelse
    </ul>
</section>
