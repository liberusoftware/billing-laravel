<?php

namespace App\Mail;

use App\Models\InvoiceDispute;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DisputeMessageReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public InvoiceDispute $dispute) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New Message on Invoice Dispute');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.disputes.message-received');
    }
}
