<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Orders\Actions\ConvertQuoteToOrder;
use Liberu\Billing\Orders\Actions\TransitionQuote;
use Liberu\Billing\Orders\Models\Quote;
use Livewire\Component;

final class QuoteList extends Component
{
    public ?int $selectedQuoteId = null;

    public string $status = 'sent';

    public function transition(TransitionQuote $transition): void
    {
        $this->validate(['selectedQuoteId' => ['required', 'integer'], 'status' => ['required', 'in:sent,viewed,accepted,declined']]);
        $quote = $this->quote();
        Gate::authorize('update', $quote);
        $transition->execute($quote, $this->status);
        session()->flash('billing-orders-quotes-message', __('Quote status updated.'));
    }

    public function convert(ConvertQuoteToOrder $convert): void
    {
        $quote = $this->quote();
        Gate::authorize('update', $quote);
        $convert->execute($quote);
        session()->flash('billing-orders-quotes-message', __('Quote converted to order.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Quote::class);
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return view('module-billing-orders-livewire::quote-list', ['quotes' => Quote::query()->where('team_id', $team)->latest()->get()]);
    }

    private function quote(): Quote
    {
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return Quote::query()->whereKey($this->selectedQuoteId)->where('team_id', $team)->firstOrFail();
    }
}
