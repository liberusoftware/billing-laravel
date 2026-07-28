<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrganisationType;
use App\Models\Invoice;
use App\Models\ResellerAgreement;
use App\Models\ResellerRevenueTransaction;
use App\Models\ResellerServiceDelegation;
use App\Models\Subscription;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ResellerService
{
    /**
     * @param  array<string, mixed>  $terms
     */
    public function createAgreement(Team $provider, Team $reseller, array $terms): ResellerAgreement
    {
        if ($reseller->parent_team_id !== $provider->id) {
            throw new InvalidArgumentException('The reseller organisation must be a child of the provider.');
        }

        if (! in_array($reseller->organisation_type, [
            OrganisationType::Reseller,
            OrganisationType::Partner,
            OrganisationType::WhiteLabel,
            OrganisationType::Franchise,
        ], true)) {
            throw new InvalidArgumentException('The child organisation type cannot hold a reseller agreement.');
        }

        return ResellerAgreement::query()->create([
            'default_discount_percent' => 0,
            'revenue_share_percent' => 0,
            'currency' => 'USD',
            'status' => 'active',
            'product_pricing' => [],
            ...$terms,
            'provider_team_id' => $provider->id,
            'reseller_team_id' => $reseller->id,
        ]);
    }

    public function wholesalePrice(
        ResellerAgreement $agreement,
        float $retailPrice,
        ?int $productServiceId = null
    ): float {
        $pricing = $productServiceId !== null
            ? (($agreement->product_pricing ?? [])[$productServiceId] ?? null)
            : null;

        if ($pricing !== null && isset($pricing['price'])) {
            return round(max(0, (float) $pricing['price']), 2);
        }

        $discount = $pricing !== null && isset($pricing['discount_percent'])
            ? (float) $pricing['discount_percent']
            : (float) $agreement->default_discount_percent;

        return round(max(0, $retailPrice * (1 - $discount / 100)), 2);
    }

    public function delegate(
        ResellerAgreement $agreement,
        Subscription $subscription,
        ?float $retailPrice = null
    ): ResellerServiceDelegation {
        if ($agreement->status !== 'active') {
            throw new InvalidArgumentException('The reseller agreement is not active.');
        }

        if ($subscription->team_id !== $agreement->provider_team_id) {
            throw new InvalidArgumentException('Only provider-owned services can be delegated.');
        }

        return DB::transaction(function () use ($agreement, $subscription, $retailPrice): ResellerServiceDelegation {
            $locked = ResellerAgreement::query()->lockForUpdate()->findOrFail($agreement->id);
            $retail = round($retailPrice ?? (float) $subscription->price, 2);
            $wholesale = $this->wholesalePrice($locked, $retail, $subscription->product_service_id);
            $newCreditUsed = round((float) $locked->credit_used + $wholesale, 2);

            if ($locked->credit_limit !== null && $newCreditUsed > (float) $locked->credit_limit) {
                throw new RuntimeException('The reseller credit limit would be exceeded.');
            }

            $delegation = $locked->delegations()->create([
                'subscription_id' => $subscription->id,
                'wholesale_price' => $wholesale,
                'retail_price' => $retail,
                'currency' => $subscription->currency,
                'status' => 'active',
            ]);
            $locked->update(['credit_used' => $newCreditUsed]);

            return $delegation;
        });
    }

    public function recordRevenue(
        ResellerServiceDelegation $delegation,
        float $grossAmount,
        ?Invoice $invoice = null
    ): ResellerRevenueTransaction {
        $agreement = $delegation->agreement;
        $resellerAmount = round($grossAmount * ((float) $agreement->revenue_share_percent / 100), 2);

        return $agreement->revenueTransactions()->create([
            'reseller_service_delegation_id' => $delegation->id,
            'invoice_id' => $invoice?->id,
            'gross_amount' => round($grossAmount, 2),
            'provider_amount' => round($grossAmount - $resellerAmount, 2),
            'reseller_amount' => $resellerAmount,
            'currency' => $delegation->currency,
            'status' => 'pending',
        ]);
    }

    public function settle(ResellerRevenueTransaction $transaction): ResellerRevenueTransaction
    {
        $transaction->update(['status' => 'settled', 'settled_at' => now()]);

        return $transaction->refresh();
    }
}
