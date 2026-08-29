<?php

use App\Models\Customer;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Billing\Orders\Actions\AddChangeOrder;
use Liberu\Billing\Orders\Actions\CheckoutCart;
use Liberu\Billing\Orders\Actions\CreateCart;
use Liberu\Billing\Orders\Actions\CreateOrder;
use Liberu\Billing\Orders\Actions\CreateQuote;
use Liberu\Billing\Orders\Actions\ReviewFraud;
use Liberu\Billing\Orders\Actions\TransitionOrder;
use Liberu\Billing\Orders\Enums\FraudReviewStatus;
use Liberu\Billing\Orders\Enums\OrderStatus;
use Liberu\Billing\Orders\Models\Cart;
use Liberu\Billing\Orders\Models\Order;
use Liberu\Billing\Orders\Models\Quote;

uses(RefreshDatabase::class);

it('creates an approved order with calculated totals', function () {
    $order = app(CreateOrder::class)->execute([
        'order_number' => 'ORD-1001',
        'currency' => 'usd',
        'subtotal_minor' => 10000,
        'discount_minor' => 1500,
        'tax_minor' => 850,
        'metadata' => ['source' => 'checkout'],
    ]);

    expect($order->order_number)->toBe('ORD-1001')
        ->and($order->currency)->toBe('USD')
        ->and($order->total_minor)->toBe(9350)
        ->and($order->status)->toBe(OrderStatus::Approved)
        ->and($order->fraud_status)->toBe(FraudReviewStatus::NotRequired);
});

it('holds orders for fraud review and supports guarded transitions', function () {
    $order = app(CreateOrder::class)->execute([
        'order_number' => 'ORD-1002',
        'currency' => 'USD',
        'subtotal_minor' => 5000,
        'fraud_review_required' => true,
    ]);

    $transitioned = app(TransitionOrder::class)->execute(
        $order,
        OrderStatus::Approved,
        FraudReviewStatus::Cleared,
    );

    expect($transitioned->status)->toBe(OrderStatus::Approved)
        ->and($transitioned->fraud_status)->toBe(FraudReviewStatus::Cleared);

    $completed = app(TransitionOrder::class)->execute($transitioned, OrderStatus::Completed);

    expect(fn () => app(TransitionOrder::class)->execute($completed, OrderStatus::Cancelled))
        ->toThrow(LogicException::class);
});

it('rejects invalid order amounts', function () {
    expect(fn () => app(CreateOrder::class)->execute([
        'currency' => 'USD',
        'subtotal_minor' => 100,
        'discount_minor' => 101,
        ]))->toThrow(InvalidArgumentException::class);
});

it('rejects a quote owned by another team', function (): void {
    $quote = Quote::query()->create([
        'team_id' => 20, 'quote_number' => 'QUO-FOREIGN', 'currency' => 'USD',
        'total_minor' => 100, 'items' => [], 'status' => 'draft',
    ]);

    expect(fn () => app(CreateOrder::class)->execute([
        'team_id' => 10, 'quote_id' => $quote->getKey(), 'currency' => 'USD', 'subtotal_minor' => 100,
    ]))->toThrow(InvalidArgumentException::class, 'Order quote reference is invalid.');
});

it('rejects foreign customer references for orders, quotes, and carts', function (): void {
    $team = Team::factory()->create(['id' => 20]);
    $customerId = Customer::factory()->create(['team_id' => $team->getKey()])->getKey();

    expect(fn () => app(CreateOrder::class)->execute([
        'team_id' => 10, 'customer_id' => $customerId, 'currency' => 'USD', 'subtotal_minor' => 100,
    ]))->toThrow(InvalidArgumentException::class, 'Customer reference is invalid.')
        ->and(fn () => app(CreateQuote::class)->execute([
            'team_id' => 10, 'customer_id' => $customerId, 'currency' => 'USD', 'total_minor' => 100, 'items' => [['name' => 'Item']],
        ]))->toThrow(InvalidArgumentException::class, 'Customer reference is invalid.')
        ->and(fn () => app(CreateCart::class)->execute([
            'team_id' => 10, 'customer_id' => $customerId, 'currency' => 'USD', 'items' => [['name' => 'Item']],
        ]))->toThrow(InvalidArgumentException::class, 'Customer reference is invalid.');
});

it('does not transition an order after its persisted state becomes terminal', function (): void {
    $order = app(CreateOrder::class)->execute([
        'order_number' => 'ORD-1003',
        'currency' => 'USD',
        'subtotal_minor' => 100,
    ]);
    $order->refresh();
    $order->update(['status' => OrderStatus::Completed]);

    expect(fn () => app(TransitionOrder::class)->execute($order, OrderStatus::Cancelled))
        ->toThrow(LogicException::class, 'Terminal orders cannot transition.');
});

it('does not review fraud after an order becomes terminal', function (): void {
    $order = app(CreateOrder::class)->execute([
        'order_number' => 'ORD-1004', 'currency' => 'USD', 'subtotal_minor' => 100,
    ]);
    $order->refresh();
    Order::query()->whereKey($order->getKey())->update(['status' => OrderStatus::Completed->value]);

    expect(fn () => app(ReviewFraud::class)->execute($order, FraudReviewStatus::Blocked))
        ->toThrow(LogicException::class, 'Terminal orders cannot receive fraud reviews.');
});

it('appends change orders from the persisted order state', function (): void {
    $order = app(CreateOrder::class)->execute([
        'order_number' => 'ORD-1005', 'currency' => 'USD', 'subtotal_minor' => 100,
    ]);
    $order->refresh();
    Order::query()->whereKey($order->getKey())->update(['change_orders' => [['reason' => 'existing']]]);

    $updated = app(AddChangeOrder::class)->execute($order, ['reason' => 'new change']);

    expect($updated->change_orders)->toHaveCount(2);
});

it('does not check out a cart after its persisted state becomes checked out', function (): void {
    $cart = Cart::query()->create(['currency' => 'USD', 'items' => [], 'status' => 'open']);
    $cart->refresh();
    Cart::query()->whereKey($cart->getKey())->update(['status' => 'checked_out']);

    expect(fn () => app(CheckoutCart::class)->execute($cart, ['subtotal_minor' => 100]))
        ->toThrow(LogicException::class, 'Only open, non-expired carts can be checked out.');
});
