<?php

namespace App\Services;

use App\Jobs\SendEmailNotification;
use Exception;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

class EmailNotificationService
{
    protected $maxRetries = 3;

    public function send(Mailable $mailable, string $recipient): bool
    {
        try {
            Queue::push(
                new SendEmailNotification(
                    $mailable,
                    $recipient
                )
            );

            Log::info(
                'Email notification queued',
                [
                    'recipient' => $recipient,
                    'mailable_class' => $mailable::class,
                ]
            );

            return true;
        } catch (Exception $e) {
            Log::error(
                'Failed to queue email notification',
                [
                    'recipient' => $recipient,
                    'mailable_class' => $mailable::class,
                    'error' => $e->getMessage(),
                ]
            );

            return false;
        }
    }

    public function sendNow(Mailable $mailable, string $recipient): bool
    {
        $attempts = 0;

        while ($attempts < $this->maxRetries) {
            try {
                Mail::to($recipient)->send($mailable);

                Log::info(
                    'Email notification sent successfully',
                    [
                        'recipient' => $recipient,
                        'mailable_class' => $mailable::class,
                        'attempt' => $attempts + 1,
                    ]
                );

                return true;
            } catch (Exception $e) {
                $attempts++;

                Log::warning(
                    'Email sending failed, retrying...',
                    [
                        'recipient' => $recipient,
                        'mailable_class' => $mailable::class,
                        'attempt' => $attempts,
                        'error' => $e->getMessage(),
                    ]
                );

                if ($attempts >= $this->maxRetries) {
                    Log::error(
                        'Email sending failed after max retries',
                        [
                            'recipient' => $recipient,
                            'mailable_class' => $mailable::class,
                            'error' => $e->getMessage(),
                        ]
                    );

                    return false;
                }

                // No in-request backoff sleep: blocking the caller for minutes is
                // never acceptable. Durable retry/backoff lives in the queued
                // SendEmailNotification job ($tries + $backoff); sendNow() is the
                // synchronous best-effort path and retries immediately.
            }
        }

        return false;
    }
}
