<?php

namespace App\Services;

use App\Models\Currency;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Core multi-currency conversion engine.
 *
 * Converts between currencies via the base currency using each currency's stored
 * exchange_rate (rate relative to base; base = 1):
 *   amount * (rateTo / rateFrom)
 *
 * ponytail: roadmap seams deliberately deferred (build when a feature actually needs them):
 *   - per-product multi-currency pricing
 *   - customer default billing currency
 *   - storing exchange_rate-used + base-equivalent on invoices/payments at generation
 *   - payment received in a different currency than the invoice
 *   - reporting currency conversion / historical-rate reporting
 *   - automatic rate sync from an external provider
 * The single-rate-per-currency model above already supports all of these; they only
 * need wiring at their call sites, not a richer engine.
 */
class CurrencyService
{
    private const int MAX_DEPTH = 5; // Reduced from 10 to be more conservative

    private array $processedCurrencies = [];

    private static bool $isProcessing = false;

    public function convert(float $amount, string $from, string $to): float
    {
        if ($from === $to) {
            return $amount;
        }

        if (self::$isProcessing) {
            throw new RuntimeException('Recursive currency conversion detected');
        }

        try {
            self::$isProcessing = true;
            $this->processedCurrencies = [];

            return $this->calculateRate(
                $amount,
                $from,
                $to,
                0
            );
        } finally {
            self::$isProcessing = false;
        }
    }

    /**
     * Convert via the base currency using each currency's stored exchange_rate.
     */
    private function calculateRate(float $amount, string $from, string $to, int $depth): float
    {
        // Prevent infinite recursion
        if ($depth >= self::MAX_DEPTH) {
            throw new RuntimeException('Maximum currency conversion depth reached');
        }

        // Prevent circular references
        $key = "{$from}-{$to}";
        if (isset($this->processedCurrencies[$key])) {
            throw new RuntimeException('Circular reference detected in currency conversion');
        }
        $this->processedCurrencies[$key] = true;

        $fromCurrency = $this->resolveCurrency($from);
        $toCurrency = $this->resolveCurrency($to);

        // Fast path: a cached direct rate overrides the DB cross-rate. Falls back to
        // the stored rate below (the old code returned $amount * null = 0 here).
        $cacheKey = "currency_rate_{$from}_{$to}";
        $rate = Cache::get($cacheKey) ?? ((float) $toCurrency->exchange_rate / (float) $fromCurrency->exchange_rate);

        unset($this->processedCurrencies[$key]);

        return round($amount * $rate, $toCurrency->decimal_precision);
    }

    /**
     * Look up an enabled currency by code, or fail loudly.
     */
    private function resolveCurrency(string $code): Currency
    {
        $currency = Currency::query()->where('code', $code)->first();

        if (! $currency instanceof Currency) {
            throw new RuntimeException("Unknown currency: {$code}");
        }

        if (! $currency->is_enabled) {
            throw new RuntimeException("Currency is disabled: {$code}");
        }

        if ((float) $currency->exchange_rate <= 0.0) {
            throw new RuntimeException("Currency has no usable exchange rate: {$code}");
        }

        return $currency;
    }

    /**
     * Clear memory
     */
    public function __destruct()
    {
        $this->processedCurrencies = [];
    }
}
