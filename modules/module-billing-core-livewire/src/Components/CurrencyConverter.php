<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Core\Actions\ConvertCurrency;
use Liberu\Billing\Core\Models\BillingCurrency;
use Livewire\Component;

final class CurrencyConverter extends Component
{
    public string $amount = '0';

    public string $from = 'USD';

    public string $to = 'EUR';

    /** @var array{amount:float,from:string,to:string,rate:float}|null */
    public ?array $result = null;

    public function convert(ConvertCurrency $convert): void
    {
        Gate::authorize('viewAny', BillingCurrency::class);
        $this->validate(['amount' => ['required', 'numeric'], 'from' => ['required', 'string', 'size:3', 'alpha'], 'to' => ['required', 'string', 'size:3', 'alpha']]);
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        abort_if($teamId === null, 403, 'A current team is required.');
        $this->result = $convert->execute((int) $teamId, (float) $this->amount, $this->from, $this->to);
    }

    public function render(): View
    {
        Gate::authorize('viewAny', BillingCurrency::class);

        return view('module-billing-core-livewire::currency-converter');
    }
}
