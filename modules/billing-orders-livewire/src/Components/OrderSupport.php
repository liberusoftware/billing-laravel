<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Orders\Models\Cart;
use Liberu\Billing\Orders\Models\Quote;
use Livewire\Component;

final class OrderSupport extends Component
{
    public function render(): View
    {
        Gate::authorize('viewAny', Quote::class);
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));

        return view('billing-orders-livewire::order-support', ['quotes' => Quote::query()->where('team_id', $team)->latest()->get(), 'carts' => Cart::query()->where('team_id', $team)->latest()->get()]);
    }
}
