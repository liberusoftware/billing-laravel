<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\Payment;
use App\Models\PaymentGateway;
use Exception;
use Illuminate\Support\Facades\Log;
use Square\Legacy\Exceptions\ApiException;
use Square\Legacy\Models\CreatePaymentRequest;
use Square\Legacy\Models\Money;
use Square\Legacy\SquareClient;
use Stripe\Charge;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\CardException;
use Stripe\Refund;
use Stripe\Stripe;

class PaymentGatewayService
{
    private int $maxRetries = 3;

    private int $retryDelay = 5; // seconds

    private array $supportedMethods = [
        'credit_card',
        'debit_card',
        'paypal',
        'google_pay',
        'apple_pay',
        'square',
        'bank_transfer',
    ];

    public function validatePaymentMethod($method): bool
    {
        return in_array(
            $method,
            $this->supportedMethods
        );
    }

    public function processPayment(Payment $payment)
    {
        $gateway = $payment->paymentGateway;
        $retries = 0;

        if (! $this->validatePaymentMethod($payment->payment_method)) {
            throw new Exception('Unsupported payment method: '.$payment->payment_method);
        }

        while ($retries < $this->maxRetries) {
            try {
                $result = $this->attemptPayment(
                    $payment,
                    $gateway
                );

                // Trigger reconciliation after successful payment
                if ($result) {
                    app(PaymentReconciliationService::class)->reconcilePayment($payment);
                }

                Log::info(
                    'Payment processed successfully',
                    [
                        'payment_id' => $payment->id,
                        'attempt' => $retries + 1,
                    ]
                );
                Log::info(
                    'Payment processed successfully',
                    [
                        'payment_id' => $payment->id,
                        'attempt' => $retries + 1,
                        'method' => $payment->payment_method,
                    ]
                );

                return $result;
            } catch (Exception $e) {
                $retries++;
                Log::warning(
                    'Payment attempt failed',
                    [
                        'payment_id' => $payment->id,
                        'attempt' => $retries,
                        'method' => $payment->payment_method,
                        'error' => $e->getMessage(),
                    ]
                );

                if ($retries >= $this->maxRetries) {
                    Log::error(
                        'Payment processing failed after max retries',
                        [
                            'payment_id' => $payment->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]
                    );
                    throw $e;
                }

                sleep($this->retryDelay);
            }
        }
    }

    private function attemptPayment(Payment $payment, PaymentGateway $gateway)
    {
        return match ($gateway->name) {
            'PayPal' => $this->processPayPalPayment($payment),
            'Stripe' => $this->processStripePayment(
                $payment,
                $gateway
            ),
            'Authorize.net' => $this->processAuthorizeNetPayment($payment),
            'Square' => $this->processSquarePayment(
                $payment,
                $gateway
            ),
            'Google Pay' => $this->processGooglePayPayment($payment),
            default => throw new Exception('Unsupported payment gateway'),
        };
    }

    public function refundPayment(Payment $payment, float $amount)
    {
        $gateway = $payment->paymentGateway;
        $retries = 0;

        while ($retries < $this->maxRetries) {
            try {
                $result = $this->attemptRefund(
                    $payment,
                    $gateway,
                    $amount
                );
                Log::info(
                    'Refund processed successfully',
                    [
                        'payment_id' => $payment->id,
                        'amount' => $amount,
                        'attempt' => $retries + 1,
                    ]
                );

                return $result;
            } catch (Exception $e) {
                $retries++;
                Log::warning(
                    'Refund attempt failed',
                    [
                        'payment_id' => $payment->id,
                        'amount' => $amount,
                        'attempt' => $retries,
                        'error' => $e->getMessage(),
                    ]
                );

                if ($retries >= $this->maxRetries) {
                    Log::error(
                        'Refund processing failed after max retries',
                        [
                            'payment_id' => $payment->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]
                    );
                    throw $e;
                }

                sleep($this->retryDelay);
            }
        }
    }

    private function attemptRefund(Payment $payment, PaymentGateway $gateway, float $amount)
    {
        return match ($gateway->name) {
            'PayPal' => $this->processPayPalRefund(),
            'Stripe' => $this->processStripeRefund(
                $payment,
                $amount
            ),
            'Authorize.net' => $this->processAuthorizeNetRefund(),
            default => throw new Exception('Unsupported payment gateway for refunds'),
        };
    }

    private function processPayPalPayment(Payment $payment): void
    {
        // Implement PayPal payment processing logic here
        // Include currency handling
        Currency::where(
            'code',
            $payment->currency
        )->firstOrFail();
        // Use $currency->code for PayPal API calls
    }

    private function processStripePayment(Payment $payment, PaymentGateway $gateway)
    {
        // Retrieve the Stripe token from the payment data
        $stripeToken = $payment->stripe_token;

        if (! $stripeToken) {
            throw new Exception('Stripe token is required for payment processing');
        }

        // Set up Stripe API key
        Stripe::setApiKey($gateway->secret_key);

        try {
            // Create a charge using the Stripe token
            $charge = Charge::create(
                [
                    'amount' => (int) ($payment->amount * 100),
                    'currency' => $payment->currency,
                    'source' => $stripeToken,
                    'description' => 'Payment for Invoice #'.$payment->invoice_id,
                ]
            );

            // Update payment with Stripe charge ID
            $payment->update(
                [
                    'transaction_id' => $charge->id,
                    'status' => 'completed',
                ]
            );

            return $charge;
        } catch (CardException $e) {
            // Handle failed charge
            $payment->update(['status' => 'failed']);
            throw new Exception(
                'Payment failed: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    private function processSquarePayment(Payment $payment, PaymentGateway $gateway)
    {
        $client = new SquareClient(
            [
                'accessToken' => $gateway->secret_key,
                'environment' => config('services.square.environment'),
            ]
        );

        try {
            $amount = (int) ($payment->amount * 100);
            $currency = strtoupper($payment->currency);

            $amountMoney = new Money;
            $amountMoney->setAmount($amount);
            $amountMoney->setCurrency($currency);

            $createPaymentRequest = new CreatePaymentRequest(
                (string) $payment->square_token,
                uniqid('', true)
            );
            $createPaymentRequest->setAmountMoney($amountMoney);
            $createPaymentRequest->setReferenceId((string) $payment->id);

            $response = $client->getPaymentsApi()->createPayment($createPaymentRequest);

            if ($response->isSuccess()) {
                $payment->update(
                    [
                        'transaction_id' => $response->getResult()->getPayment()->getId(),
                        'status' => 'completed',
                    ]
                );

                return $response->getResult()->getPayment();
            }

            throw new Exception($response->getErrors()[0]->getDetail());
        } catch (ApiException $e) {
            $payment->update(['status' => 'failed']);
            throw new Exception(
                'Square payment failed: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    private function processGooglePayPayment(Payment $payment)
    {
        try {
            $paymentToken = $payment->google_pay_token;
            if (! $paymentToken) {
                throw new Exception('Google Pay token is required');
            }

            // Process payment through Google Pay API
            $response = $this->processGooglePayToken();

            $payment->update(
                [
                    'transaction_id' => $response['transaction_id'],
                    'status' => $response['status'],
                ]
            );

            return $response;
        } catch (Exception $e) {
            $payment->update(['status' => 'failed']);
            throw new Exception(
                'Google Pay payment failed: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    private function processGooglePayToken(): array
    {
        // Implement actual Google Pay API integration here
        // This is a placeholder implementation
        return [
            'success' => true,
            'transaction_id' => 'gp_'.uniqid(),
            'status' => 'completed',
        ];
    }

    private function processAuthorizeNetPayment(Payment $payment): void
    {
        // Implement Authorize.net payment processing logic here
        // Include currency handling
        Currency::where(
            'code',
            $payment->currency
        )->firstOrFail();
        // Use $currency->code for Authorize.net API calls
    }

    private function processStripeRefund(Payment $payment, float $amount): array
    {
        Stripe::setApiKey($payment->paymentGateway->secret_key);

        try {
            $refund = Refund::create(
                [
                    'charge' => $payment->transaction_id,
                    'amount' => (int) ($amount * 100),
                    // Convert to cents
                    'reason' => 'requested_by_customer',
                ]
            );

            return [
                'success' => true,
                'transaction_id' => $refund->id,
                'message' => 'Refund processed successfully',
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function processPayPalRefund(): array
    {
        // Implement PayPal refund logic here
        return [
            'success' => true,
            'message' => 'PayPal refund processed successfully',
        ];
    }

    private function processAuthorizeNetRefund(): array
    {
        // Implement Authorize.net refund logic here
        return [
            'success' => true,
            'message' => 'Authorize.net refund processed successfully',
        ];
    }
}
