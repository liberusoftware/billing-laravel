<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceGenerated extends Mailable
{
    use Queueable, SerializesModels;

    public Invoice $invoice;

    protected $template;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
        $this->template = EmailTemplate::getTemplate(
            'invoice_generated',
            $invoice->team_id
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->template ?
                $this->parseTemplate($this->template->subject) :
                'Invoice Generated',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.dynamic-template',
            with: [
                'content' => $this->template ?
                    $this->parseTemplate($this->template->body) :
                    view(
                        'emails.invoice-generated',
                        ['invoice' => $this->invoice]
                    )->render(),
                'invoice' => $this->invoice,
            ],
        );
    }

    protected function parseTemplate($text): string
    {
        $replacements = [
            '{{invoice_number}}' => e($this->invoice->invoice_number),
            '{{amount}}' => e((string) $this->invoice->total_amount),
            '{{due_date}}' => e($this->invoice->due_date->format('Y-m-d')),
            '{{customer_name}}' => e($this->invoice->customer->name),
        ];

        return strtr(
            $text,
            $replacements
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
