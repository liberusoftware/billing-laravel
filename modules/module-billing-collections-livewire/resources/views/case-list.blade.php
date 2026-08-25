<div>
    @if (session('module-billing-collections-message'))<div>{{ session('module-billing-collections-message') }}</div>@endif
    <button type="button" wire:click="$set('showCreate', true)">{{ __('Open collection case') }}</button>
    @if ($showCreate)
        <form wire:submit="create">
            <input wire:model="amountMinor" type="number" min="1" aria-label="{{ __('Amount') }}">
            <input wire:model="currency" maxlength="3" aria-label="{{ __('Currency') }}">
            <button type="submit">{{ __('Open') }}</button>
        </form>
    @endif
    <label>{{ __('Next action') }} <input type="datetime-local" wire:model="operationDate"></label><label>{{ __('Reason') }} <input wire:model="operationReason"></label><label>{{ __('Credit-control level') }} <input wire:model="creditControlLevel"></label>
    <ul>@foreach ($cases as $case)<li>{{ $case->id }} — {{ $case->status->value }} — {{ $case->amount_minor }} {{ $case->currency }} <button type="button" wire:click="promise({{ $case->id }})">{{ __('Promise') }}</button><button type="button" wire:click="retry({{ $case->id }})">{{ __('Retry') }}</button><button type="button" wire:click="dunning({{ $case->id }})">{{ __('Dunning') }}</button><button type="button" wire:click="reminder({{ $case->id }})">{{ __('Reminder') }}</button><button type="button" wire:click="suspend({{ $case->id }})">{{ __('Suspend') }}</button><button type="button" wire:click="writeOff({{ $case->id }})">{{ __('Write off') }}</button><button type="button" wire:click="recover({{ $case->id }})">{{ __('Recover') }}</button><button type="button" wire:click="creditControl({{ $case->id }})">{{ __('Credit control') }}</button></li>@endforeach</ul>
</div>
