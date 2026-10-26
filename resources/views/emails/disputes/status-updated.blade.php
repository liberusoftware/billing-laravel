@component('mail::message')
# {{ __('Invoice Dispute Status Updated') }}

{{ __('Invoice :number', ['number' => $dispute->invoice?->invoice_number]) }}

{{ __('Status: :status', ['status' => $dispute->status]) }}

{{ $dispute->resolution_notes }}
@endcomponent
