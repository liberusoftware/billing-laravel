<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Billing\Orders\Actions\CreateOrder;
use Liberu\Billing\Orders\Actions\TransitionOrder;
use Liberu\Billing\Orders\Enums\FraudReviewStatus;
use Liberu\Billing\Orders\Enums\OrderStatus;

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
