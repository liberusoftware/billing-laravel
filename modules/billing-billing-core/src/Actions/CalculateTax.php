<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Actions;

use InvalidArgumentException;
use Liberu\Billing\Core\Models\BillingTaxProfile;

final class CalculateTax
{
    /** @return array{subtotal:float,tax:float,total:float,rate:float,inclusive:bool,jurisdiction:string|null} */
    public function execute(int $teamId, float $amount, ?string $jurisdiction = null): array
    {
        if ($teamId < 1 || $amount < 0) {
            throw new InvalidArgumentException('A team and non-negative amount are required.');
        }

        $profile = BillingTaxProfile::query()->where('team_id', $teamId)->where('enabled', true)->when($jurisdiction !== null, fn ($query) => $query->where(fn ($nested) => $nested->where('jurisdiction', $jurisdiction)->orWhereNull('jurisdiction')))->orderByRaw('jurisdiction is null')->first();
        $rate = $profile instanceof BillingTaxProfile ? (float) $profile->rate / 100 : 0.0;
        $inclusive = $profile instanceof BillingTaxProfile && (bool) $profile->inclusive;
        $tax = $inclusive ? $amount - ($amount / (1 + $rate)) : $amount * $rate;

        return ['subtotal' => round($inclusive ? $amount - $tax : $amount, 2), 'tax' => round($tax, 2), 'total' => round($inclusive ? $amount : $amount + $tax, 2), 'rate' => $rate * 100, 'inclusive' => $inclusive, 'jurisdiction' => $profile?->jurisdiction];
    }
}
