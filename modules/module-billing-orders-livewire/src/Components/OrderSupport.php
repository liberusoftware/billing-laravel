<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Orders\Actions\CheckoutCart;
use Liberu\Billing\Orders\Actions\CreateCart;
use Liberu\Billing\Orders\Actions\CreateQuote;
use Liberu\Billing\Orders\Actions\TransitionOrder;
use Liberu\Billing\Orders\Enums\OrderStatus;
use Liberu\Billing\Orders\Models\Cart;
use Liberu\Billing\Orders\Models\Order;
use Liberu\Billing\Orders\Models\Quote;
use Livewire\Component;

final class OrderSupport extends Component
{
    public string $currency = 'USD';

    public int $customerId = 0;

    public int $quoteTotalMinor = 0;

    public string $quoteItems = '[]';

    public string $cartItems = '[]';

    public ?int $selectedCartId = null;

    public int $cartSubtotalMinor = 0;

    public ?int $selectedOrderId = null;

    public string $orderStatus = 'pending_review';

    public function createQuote(CreateQuote $create): void
    {
        Gate::authorize('create', Quote::class);
        $this->validate(['currency' => ['required', 'string', 'size:3', 'alpha'], 'quoteTotalMinor' => ['required', 'integer', 'min:0'], 'quoteItems' => ['required', 'json'], 'customerId' => ['nullable', 'integer', 'min:0']]);
        $create->execute(['team_id' => $this->teamId(), 'customer_id' => $this->customerId > 0 ? $this->customerId : null, 'currency' => $this->currency, 'total_minor' => $this->quoteTotalMinor, 'items' => $this->decodeItems($this->quoteItems)]);
        $this->reset(['quoteTotalMinor', 'quoteItems', 'customerId']);
        $this->quoteItems = '[]';
        session()->flash('billing-orders-support-message', __('Quote created.'));
    }

    public function createCart(CreateCart $create): void
    {
        Gate::authorize('create', Cart::class);
        $this->validate(['currency' => ['required', 'string', 'size:3', 'alpha'], 'cartItems' => ['required', 'json'], 'customerId' => ['nullable', 'integer', 'min:0']]);
        $create->execute(['team_id' => $this->teamId(), 'customer_id' => $this->customerId > 0 ? $this->customerId : null, 'currency' => $this->currency, 'items' => $this->decodeItems($this->cartItems)]);
        $this->reset(['cartItems', 'customerId']);
        $this->cartItems = '[]';
        session()->flash('billing-orders-support-message', __('Cart created.'));
    }

    public function checkoutCart(CheckoutCart $checkout): void
    {
        Gate::authorize('create', Order::class);
        $this->validate(['selectedCartId' => ['required', 'integer', 'min:1'], 'cartSubtotalMinor' => ['required', 'integer', 'min:0']]);
        $cart = Cart::query()->whereKey($this->selectedCartId)->where('team_id', $this->teamId())->firstOrFail();
        Gate::authorize('update', $cart);
        $checkout->execute($cart, ['subtotal_minor' => $this->cartSubtotalMinor]);
        $this->reset(['selectedCartId', 'cartSubtotalMinor']);
        session()->flash('billing-orders-support-message', __('Cart checked out.'));
    }

    public function transitionOrder(TransitionOrder $transition): void
    {
        $this->validate(['selectedOrderId' => ['required', 'integer', 'min:1'], 'orderStatus' => ['required', 'in:draft,pending_review,approved,rejected,cancelled,completed']]);
        $order = Order::query()->whereKey($this->selectedOrderId)->where('team_id', $this->teamId())->firstOrFail();
        Gate::authorize('update', $order);
        $transition->execute($order, OrderStatus::from($this->orderStatus));
        $this->reset('selectedOrderId');
        session()->flash('billing-orders-support-message', __('Order status updated.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Quote::class);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));

        return view('module-billing-orders-livewire::order-support', ['quotes' => Quote::query()->where('team_id', $team)->latest()->get(), 'carts' => Cart::query()->where('team_id', $team)->latest()->get(), 'orders' => Order::query()->where('team_id', $team)->latest()->get()]);
    }

    /** @return array<int,mixed> */
    private function decodeItems(string $items): array
    {
        $decoded = json_decode($items, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new \InvalidArgumentException('Order items must be a JSON array.');
        }

        return array_values($decoded);
    }

    private function teamId(): int
    {
        return (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
    }
}
