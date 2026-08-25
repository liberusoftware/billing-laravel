<section aria-labelledby="module-billing-subscriptions-entitlements-heading">
    <h2 id="module-billing-subscriptions-entitlements-heading">{{ __('Subscription entitlements') }}</h2>
    @if (session('module-billing-subscriptions-entitlements-message'))<p role="status">{{ session('module-billing-subscriptions-entitlements-message') }}</p>@endif
    <form wire:submit="updateEntitlements"><label>{{ __('Entitlements (JSON object)') }} <textarea wire:model="entitlements" required></textarea></label><button type="submit">{{ __('Update entitlements') }}</button></form>
    <ul wire:loading.class="opacity-50">@forelse ($subscriptions as $subscription)<li wire:key="subscription-entitlement-{{ $subscription->id }}">Subscription {{ $subscription->id }}: {{ json_encode($subscription->entitlement_state ?? []) }} <button type="button" wire:click="$set('selectedSubscriptionId', {{ $subscription->id }})">{{ __('Select') }}</button></li>@empty<li>{{ __('No subscription entitlements found.') }}</li>@endforelse</ul>
</section>
