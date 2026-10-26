@component('mail::message')
# {{ __('New Message on Invoice Dispute') }}

{{ __('A new message has been added to the dispute for invoice :number.', ['number' => $dispute->invoice?->invoice_number]) }}

{{ __('Sign in to your account to review the conversation.') }}
@endcomponent
