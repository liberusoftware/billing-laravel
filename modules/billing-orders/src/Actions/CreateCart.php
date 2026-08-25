<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Billing\Orders\Models\Cart;

final class CreateCart
{
    /** @param array<string,mixed> $attributes */
    public function execute(array $attributes): Cart
    {
        if (empty($attributes['items'])) {
            throw new \InvalidArgumentException('A cart must contain items.');
        }

        return DB::transaction(fn (): Cart => Cart::query()->create(['team_id' => $attributes['team_id'] ?? null, 'customer_id' => $attributes['customer_id'] ?? null, 'currency' => strtoupper($attributes['currency']), 'items' => $attributes['items'], 'expires_at' => $attributes['expires_at'] ?? null, 'status' => 'open']));
    }
}
