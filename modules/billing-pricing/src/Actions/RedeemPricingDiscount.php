<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Pricing\Models\PricingDiscount;

final readonly class RedeemPricingDiscount
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(PricingDiscount $discount): PricingDiscount
    {
        if (! $discount->active || ($discount->max_redemptions !== null && $discount->redemptions >= $discount->max_redemptions) || ($discount->starts_at?->isFuture() ?? false) || ($discount->ends_at?->isPast() ?? false)) {
            throw new \LogicException('This discount is not redeemable.');
        }

return $this->database->transaction(function () use ($discount): PricingDiscount {
            $discount->increment('redemptions');

            return $discount->refresh();
        });
    }
}
