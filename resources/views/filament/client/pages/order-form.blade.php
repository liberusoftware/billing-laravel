<x-filament-panels::page>
    <div class="space-y-2">
        <h2 class="text-xl font-semibold">{{ $template->name }}</h2>
        @if ($template->description)
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $template->description }}</p>
        @endif
        <p class="text-sm text-gray-600 dark:text-gray-400">Choose your service and billing period, then review your order before continuing to payment.</p>
    </div>

    <form wire:submit="placeOrder" class="space-y-6">
        {{ $this->form }}

        @if ($total = $this->orderTotal())
            <x-filament::section heading="Order summary">
                <dl class="space-y-4" aria-live="polite">
                    <div class="flex flex-wrap justify-between gap-2">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Initial total</dt>
                        <dd class="text-xl font-semibold">{{ $total }}</dd>
                    </div>
                    @if ($billingCycle !== 'one-time')
                        <div class="flex flex-wrap justify-between gap-2">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Renewal total ({{ $billingCycle }})</dt>
                            <dd class="font-semibold">{{ $total }}</dd>
                        </div>
                    @endif
                </dl>
                @if ($this->requiresDomain())
                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">Hosting activation begins after payment is confirmed. Use a domain you already own; domain registration and transfer are not included.</p>
                @endif
                <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">Placing an order creates your invoice. You can review it before making a payment.</p>
            </x-filament::section>
        @endif

        <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="placeOrder">
            <span wire:loading.remove wire:target="placeOrder">Place order &amp; view invoice</span>
            <span wire:loading wire:target="placeOrder">Creating your order…</span>
        </x-filament::button>
    </form>
</x-filament-panels::page>
