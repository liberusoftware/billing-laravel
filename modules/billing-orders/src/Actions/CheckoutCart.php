<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Orders\Models\Cart;
use Liberu\Billing\Orders\Models\Order;

final readonly class CheckoutCart
{
    public function __construct(private DatabaseManager $database, private CreateOrder $createOrder) {}

    public function execute(Cart $cart, array $attributes): Order
    {
        if ($cart->status !== 'open' || ($cart->expires_at !== null && $cart->expires_at->isPast())) {
            throw new \LogicException('Only open, non-expired carts can be checked out.');
        }

        return $this->database->transaction(function () use ($cart, $attributes): Order {
            $order = $this->createOrder->execute([...$attributes, 'team_id' => $cart->team_id, 'customer_id' => $cart->customer_id, 'currency' => $cart->currency]);
            $cart->update(['status' => 'checked_out']);

            return $order;
        });
    }
}
