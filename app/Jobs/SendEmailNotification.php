<?php

declare(strict_types=1);

namespace App\Jobs;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendEmailNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 300; // 5 minutes

    public function __construct(protected Mailable $mailable, protected string $recipient) {}

    public function handle(): void
    {
        try {
            Mail::to($this->recipient)->send($this->mailable);

            Log::info(
                'Queued email sent successfully',
                [
                    'recipient' => $this->recipient,
                    'mailable_class' => $this->mailable::class,
                ]
            );
        } catch (Exception $e) {
            Log::error(
                'Failed to send queued email',
                [
                    'recipient' => $this->recipient,
                    'mailable_class' => $this->mailable::class,
                    'error' => $e->getMessage(),
                    'attempt' => $this->attempts(),
                ]
            );

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error(
            'Email job failed permanently',
            [
                'recipient' => $this->recipient,
                'mailable_class' => $this->mailable::class,
                'error' => $exception->getMessage(),
            ]
        );
    }
}
