<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Actions;

use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Liberu\Billing\Core\Models\BillingCurrency;

final class ConvertCurrency
{
    /** @return array{amount: float, from: string, to: string, rate: float} */
    public function execute(int $teamId, float $amount, string $from, string $to): array
    {
        $from = strtoupper(trim($from));
        $to = strtoupper(trim($to));
        if ($teamId < 1 || $from === '' || $to === '') {
            throw new InvalidArgumentException('A team and currency codes are required.');
        }

        $fromCurrency = $this->resolve($teamId, $from);
        $toCurrency = $this->resolve($teamId, $to);
        $rate = $from === $to ? 1.0 : (float) (Cache::get("billing.currency.rate.{$from}.{$to}") ?? ((float) $toCurrency->exchange_rate / (float) $fromCurrency->exchange_rate));
        if ($rate <= 0) {
            throw new InvalidArgumentException('Currency exchange rates must be positive.');
        }

        return ['amount' => round($amount * $rate, (int) $toCurrency->decimal_places), 'from' => $from, 'to' => $to, 'rate' => $rate];
    }

    private function resolve(int $teamId, string $code): BillingCurrency
    {
        $currency = BillingCurrency::query()->where('code', $code)->where(fn ($query) => $query->where('team_id', $teamId)->orWhereNull('team_id'))->orderByRaw('team_id is null')->first();
        if (! $currency instanceof BillingCurrency || ! $currency->enabled) {
            throw new InvalidArgumentException("Currency is unavailable: {$code}.");
        }
        if ((float) $currency->exchange_rate <= 0) {
            throw new InvalidArgumentException("Currency has no usable exchange rate: {$code}.");
        }

        return $currency;
    }
}
