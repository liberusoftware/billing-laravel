@component('mail::message')
# {{ __('Invoice Dispute Created') }}

{{ __('Invoice :number', ['number' => $dispute->invoice?->invoice_number]) }}

{{ __('Reason: :reason', ['reason' => $dispute->reason]) }}

{{ $dispute->description }}
@endcomponent
