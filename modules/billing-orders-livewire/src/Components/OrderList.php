<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Livewire\Components;

use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Orders\Actions\CreateOrder;
use Liberu\Billing\Orders\Models\Order;
use Liberu\Billing\Orders\Queries\ListOrders;
use Livewire\Component;

final class OrderList extends Component
{
    public string $currency = 'USD';

    public int $subtotalMinor = 0;

    public bool $showCheckout = false;

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
