<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Orders\Actions\AddChangeOrder;
use Liberu\Billing\Orders\Actions\CreateOrder;
use Liberu\Billing\Orders\Actions\ReviewFraud;
use Liberu\Billing\Orders\Enums\FraudReviewStatus;
use Liberu\Billing\Orders\Models\Order;
use Liberu\Billing\Orders\Queries\ListOrders;
use Livewire\Component;

final class OrderList extends Component
{
    public string $currency = 'USD';

    public int $subtotalMinor = 0;

    public bool $showCheckout = false;

    public ?int $selectedOrderId = null;

    public string $fraudStatus = 'pending';

    public string $changeReason = '';

    public int $changeAmountMinor = 0;

    public function reviewFraud(int $orderId, ReviewFraud $review): void
    {
        $this->validate(['fraudStatus' => ['required', 'in:not_required,pending,cleared,blocked']]);
        $order = $this->authorizedOrder($orderId);
        $review->execute($order, FraudReviewStatus::from($this->fraudStatus));
        session()->flash('billing-orders-message', __('Fraud review updated.'));
    }

    public function addChangeOrder(int $orderId, AddChangeOrder $change): void
    {
        $this->validate(['changeReason' => ['required', 'string', 'max:1000'], 'changeAmountMinor' => ['required', 'integer', 'min:0']]);
        $change->execute($this->authorizedOrder($orderId), ['reason' => $this->changeReason, 'amount_minor' => $this->changeAmountMinor]);
        $this->reset(['selectedOrderId', 'changeReason', 'changeAmountMinor']);
        session()->flash('billing-orders-message', __('Change order added.'));
    }

    private function authorizedOrder(int $orderId): Order
    {
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $order = Order::query()->whereKey($orderId)->where('team_id', $team)->firstOrFail();
        Gate::authorize('update', $order);

        return $order;
    }

    public function checkout(CreateOrder $create): void
    {
        Gate::authorize('create', Order::class);
        $this->validate(['currency' => ['required', 'string', 'size:3', 'alpha'], 'subtotalMinor' => ['required', 'integer', 'min:0']]);
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $create->execute(['currency' => $this->currency, 'subtotal_minor' => $this->subtotalMinor, 'team_id' => $teamId]);
        $this->reset('subtotalMinor');
        $this->showCheckout = false;
        session()->flash('billing-orders-message', __('Order created.'));
    }

    public function render(ListOrders $query): View
    {
        Gate::authorize('viewAny', Order::class);
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return view('billing-orders-livewire::order-list', ['orders' => $query->execute($teamId === null ? null : (int) $teamId)]);
    }
}
