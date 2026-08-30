<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Liberu\Billing\Orders\Models\Cart;
use Liberu\Billing\Orders\Models\Order;

final class CartCheckedOut implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Cart $cart, public readonly Order $order) {}
}
