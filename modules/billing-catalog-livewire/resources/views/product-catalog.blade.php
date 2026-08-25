<section aria-labelledby="billing-catalog-heading">
    <h2 id="billing-catalog-heading">{{ __('Catalog products') }}</h2>
    @if (session()->has('billing-catalog-message')) <p role="status">{{ session('billing-catalog-message') }}</p> @endif
    <button type="button" wire:click="$set('showCreate', true)">{{ __('Create product') }}</button>
    @if ($showCreate)
        <form wire:submit="save">
            <label>{{ __('Name') }} <input wire:model="name" required /></label>
            <label>{{ __('SKU') }} <input wire:model="sku" required /></label>
            <label>{{ __('Price in minor units') }} <input type="number" min="0" wire:model="basePriceMinor" required /></label>
            <label>{{ __('Currency') }} <input wire:model="currency" maxlength="3" required /></label>
            <button type="submit">{{ __('Save') }}</button>
        </form>
    @endif
    <ul>
        @forelse ($products as $product)
            <li wire:key="product-{{ $product->id }}">{{ $product->name }} ({{ $product->sku }}) — {{ $product->base_price_minor }} {{ $product->currency }} ({{ $product->status->value }}) <button type="button" wire:click="$set('selectedProductId', {{ $product->id }})">{{ __('Select') }}</button></li>
        @empty <li>{{ __('No products found.') }}</li> @endforelse
    </ul>
    @if ($selectedProductId)
        <form wire:submit="transition">
            <select wire:model="status"><option value="draft">{{ __('Draft') }}</option><option value="active">{{ __('Active') }}</option><option value="archived">{{ __('Archived') }}</option></select>
            <button type="submit">{{ __('Update lifecycle') }}</button>
        </form>
    @endif
</section>
