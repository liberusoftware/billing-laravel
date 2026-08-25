<div wire:loading.class="opacity-50">
    <label>{{ __('Meter') }} <select wire:model.live="meterId"><option value="">{{ __('Select a meter') }}</option>@foreach ($meters as $meter)<option wire:key="usage-meter-{{ $meter->id }}" value="{{ $meter->id }}">{{ $meter->name }}</option>@endforeach</select></label>
    @if ($summary)<dl><dt>{{ __('Quantity') }}</dt><dd>{{ $summary->quantity }}</dd><dt>{{ __('Amount') }}</dt><dd>{{ $summary->amount_minor }}</dd></dl>@else<p>{{ __('No usage recorded.') }}</p>@endif
</div>
