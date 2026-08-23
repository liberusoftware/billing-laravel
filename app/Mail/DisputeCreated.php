<?php

namespace App\Mail;

use App\Models\InvoiceDispute;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DisputeCreated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public InvoiceDispute $dispute) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Invoice Dispute Created');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.disputes.created');
    }
}
