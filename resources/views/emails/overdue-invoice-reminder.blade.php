@component('mail::message')
# {{ __('Overdue Invoice Reminder') }}

{{ __('Invoice :number', ['number' => $invoice->invoice_number]) }}

{{ __('Invoice total: :amount :currency', ['amount' => number_format((float) $invoice->total_amount, 2), 'currency' => $invoice->currency]) }}

{{ __('Due date: :date', ['date' => optional($invoice->due_date)->toFormattedDateString()]) }}
@endcomponent
