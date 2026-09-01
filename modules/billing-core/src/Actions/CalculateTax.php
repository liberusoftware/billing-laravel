<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Actions;

use InvalidArgumentException;
use Liberu\Billing\Core\Models\BillingTaxExemption;
use Liberu\Billing\Core\Models\BillingTaxProfile;

final class CalculateTax
{
    /** @return array{subtotal:float,tax:float,total:float,rate:float,inclusive:bool,jurisdiction:string|null,exempt:bool} */
    public function execute(int $teamId, float $amount, ?string $jurisdiction = null, ?int $customerId = null): array
    {
        if ($teamId < 1 || $amount < 0) {
            throw new InvalidArgumentException('A team and non-negative amount are required.');
        }

        $exempt = $customerId !== null && BillingTaxExemption::query()->where('team_id', $teamId)->where('customer_id', $customerId)->where('enabled', true)->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))->exists();
        if ($exempt) {
            return ['subtotal' => $amount, 'tax' => 0.0, 'total' => $amount, 'rate' => 0.0, 'inclusive' => false, 'jurisdiction' => $jurisdiction, 'exempt' => true];
        }

        $profile = BillingTaxProfile::query()->where('team_id', $teamId)->where('enabled', true)->when($jurisdiction !== null, fn ($query) => $query->where(fn ($nested) => $nested->where('jurisdiction', $jurisdiction)->orWhereNull('jurisdiction')))->orderByRaw('jurisdiction is null')->first();
        $rate = $profile instanceof BillingTaxProfile ? (float) $profile->rate / 100 : 0.0;
        $inclusive = $profile instanceof BillingTaxProfile && (bool) $profile->inclusive;
        $taxableAmount = $inclusive && $rate > 0 ? $amount / (1 + $rate) : $amount;
        $tax = $this->taxForAmount($taxableAmount, $profile);

        return ['subtotal' => round($inclusive ? $taxableAmount : $amount, 2), 'tax' => round($tax, 2), 'total' => round($inclusive ? $amount : $amount + $tax, 2), 'rate' => $rate * 100, 'inclusive' => $inclusive, 'jurisdiction' => $profile?->jurisdiction, 'exempt' => false];
    }

    private function taxForAmount(float $amount, ?BillingTaxProfile $profile): float
    {
        if (! $profile instanceof BillingTaxProfile) {
            return 0.0;
        }
        $threshold = $profile->threshold_amount === null ? null : (float) $profile->threshold_amount;
        $thresholdRate = $profile->threshold_rate === null ? (float) $profile->rate : (float) $profile->threshold_rate;
        if ($threshold === null || $amount <= $threshold) {
            return $amount * ((float) $profile->rate / 100);
        }

        return ($threshold * (float) $profile->rate / 100) + (($amount - $threshold) * $thresholdRate / 100);
    }
}
