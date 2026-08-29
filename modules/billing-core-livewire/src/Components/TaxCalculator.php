<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Core\Actions\CalculateTax;
use Liberu\Billing\Core\Models\BillingTaxProfile;
use Livewire\Component;

final class TaxCalculator extends Component
{
    public string $amount = '0';

    public string $jurisdiction = '';

    public string $customerId = '';

    /** @var array{subtotal:float,tax:float,total:float,rate:float,inclusive:bool,jurisdiction:string|null}|null */
    public ?array $result = null;

    public function calculate(CalculateTax $calculate): void
    {
        Gate::authorize('viewAny', BillingTaxProfile::class);
        $this->validate(['amount' => ['required', 'numeric', 'min:0'], 'jurisdiction' => ['nullable', 'string', 'max:100'], 'customerId' => ['nullable', 'integer', 'min:1']]);
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        abort_if($teamId === null, 403, 'A current team is required.');
        $this->result = $calculate->execute((int) $teamId, (float) $this->amount, $this->jurisdiction ?: null, $this->customerId !== '' ? (int) $this->customerId : null);
    }

    public function render(): View
    {
        Gate::authorize('viewAny', BillingTaxProfile::class);

        return view('billing-core-livewire::tax-calculator');
    }
}
