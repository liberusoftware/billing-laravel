<section aria-labelledby="module-billing-domains-search-heading" wire:loading.class="opacity-50">
    <h2 id="module-billing-domains-search-heading">{{ __('Search domains') }}</h2>
    <form wire:submit="search">
        <label>{{ __('Domain') }} <input wire:model="domain" type="text" required></label>
        <label>{{ __('Registrar') }} <input wire:model="registrar" type="text" required></label>
        @error('domain')<span role="alert">{{ $message }}</span>@enderror
        @error('registrar')<span role="alert">{{ $message }}</span>@enderror
        <button type="submit">{{ __('Search') }}</button>
    </form>
    @if ($result)
        <p role="status">{{ $result['domain'] }} — {{ $result['available'] ? __('Available') : __('Unavailable') }}</p>
        @if ($result['price'] !== null)<p>{{ __('Price') }}: {{ $result['price'] }}</p>@endif
        @if (! empty($result['suggestions']))
            <ul>@foreach ($result['suggestions'] as $suggestion)<li>{{ $suggestion['domain'] }} — {{ $suggestion['available'] ? __('Available') : __('Unavailable') }}</li>@endforeach</ul>
        @endif
    @endif
</section>
