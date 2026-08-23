<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UpcomingInvoiceReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public $data, public $template) {}

    public function build(): self
    {
        return $this->subject($this->parseTemplate($this->template->subject))
            ->view('emails.upcoming-invoice-reminder')
            ->with(
                [
                    'content' => $this->parseTemplate($this->template->body),
                    'data' => $this->data,
                ]
            );
    }

    private function parseTemplate($text)
    {
        foreach ($this->data as $key => $value) {
            $text = str_replace(
                '{{'.$key.'}}',
                e($value),
                $text
            );
        }

        return $text;
    }
}
