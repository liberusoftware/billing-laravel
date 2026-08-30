<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Orders\Events\CartCheckedOut;
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
            $locked = Cart::query()->lockForUpdate()->findOrFail($cart->getKey());
            if ($locked->status !== 'open' || ($locked->expires_at !== null && $locked->expires_at->isPast())) {
                throw new \LogicException('Only open, non-expired carts can be checked out.');
            }

            $order = $this->createOrder->execute([...$attributes, 'team_id' => $locked->team_id, 'customer_id' => $locked->customer_id, 'currency' => $locked->currency]);
            $locked->update(['status' => 'checked_out']);
            CartCheckedOut::dispatch($locked, $order);

            return $order;
        });
    }
}
