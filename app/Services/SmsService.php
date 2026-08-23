<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected $apiKey;

    protected $from;

    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.sms.api_key');
        $this->from = config('services.sms.from_number');
        $this->baseUrl = config('services.sms.base_url');
    }

    public function send($to, $message): bool
    {
        if (! config('services.sms.enabled', true)) {
            Log::info('SMS sending skipped: SMS is disabled', ['to' => $to]);

            return false;
        }

        $formatted = $this->formatPhoneNumber($to);

        if ($formatted === '' || $formatted === '+') {
            Log::warning('SMS sending skipped: no phone number', ['to' => $to]);

            return false;
        }

        try {
            $response = Http::withHeaders(
                [
                    'Authorization' => 'Bearer '.$this->apiKey,
                ]
            )->post(
                $this->baseUrl.'/messages',
                [
                    'from' => $this->from,
                    'to' => $formatted,
                    'message' => $message,
                ]
            );

            if ($response->successful()) {
                Log::info(
                    'SMS sent successfully',
                    [
                        'to' => $to,
                        'message' => $message,
                    ]
                );

                return true;
            }

            Log::error(
                'SMS sending failed',
                [
                    'to' => $to,
                    'error' => $response->body(),
                ]
            );

            return false;

        } catch (Exception $e) {
            Log::error(
                'SMS sending failed',
                [
                    'to' => $to,
                    'error' => $e->getMessage(),
                ]
            );

            return false;
        }
    }

    protected function formatPhoneNumber($number)
    {
        // Remove any non-numeric characters
        $cleaned = preg_replace(
            '/[^0-9]/',
            '',
            (string) $number
        );

        // Ensure number starts with country code
        if (strlen((string) $cleaned) === 10) {
            return '+1'.$cleaned; // Default to US/Canada
        }

        return '+'.$cleaned;
    }
}
