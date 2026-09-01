<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Billing\Orders\Events\CartCreated;
use Liberu\Billing\Orders\Models\Cart;
use Liberu\Billing\Orders\Support\CustomerReference;

final class CreateCart
{
    /** @param array<string,mixed> $attributes */
    public function execute(array $attributes): Cart
    {
        if (empty($attributes['items'])) {
            throw new \InvalidArgumentException('A cart must contain items.');
        }

        $teamId = $attributes['team_id'] ?? null;
        $customerId = CustomerReference::assertBelongsToTeam(app('db'), $attributes['customer_id'] ?? null, $teamId);

        return DB::transaction(function () use ($teamId, $customerId, $attributes): Cart {
            $cart = Cart::query()->create(['team_id' => $teamId, 'customer_id' => $customerId, 'currency' => strtoupper($attributes['currency']), 'items' => $attributes['items'], 'expires_at' => $attributes['expires_at'] ?? null, 'status' => 'open']);
            CartCreated::dispatch($cart);

            return $cart;
        });
    }
}
