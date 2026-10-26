@component('mail::message')
# {{ __('Payment Confirmation') }}

{{ __('Invoice :number', ['number' => $payment->invoice?->invoice_number]) }}

{{ __('Payment received: :amount :currency', ['amount' => number_format((float) $payment->amount, 2), 'currency' => $payment->currency]) }}
@endcomponent
