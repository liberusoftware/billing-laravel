<section aria-labelledby="module-billing-domains-heading">
    <h2 id="module-billing-domains-heading">{{ __('Domains') }}</h2>
    @if (session('module-billing-domains-message'))<p role="status">{{ session('module-billing-domains-message') }}</p>@endif
    <button type="button" wire:click="$toggle('showCreate')">{{ __('Add domain') }}</button>
    @if ($showCreate)
        <form wire:submit="createDomain">
            <label>{{ __('Domain') }} <input wire:model="name" type="text" required></label>
            <label>{{ __('Registrar') }} <input wire:model="registrar" type="text"></label>
            @error('name')<span role="alert">{{ $message }}</span>@enderror
            <button type="submit">{{ __('Save') }}</button>
        </form>
    @endif
    <ul>
        @foreach ($domains as $domain)<li>{{ $domain->name }} — {{ $domain->status }}</li>@endforeach
    </ul>
</section>
