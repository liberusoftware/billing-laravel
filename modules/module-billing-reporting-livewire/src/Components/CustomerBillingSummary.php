<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Reporting\Actions\GenerateCustomerBillingSummary;
use Liberu\Billing\Reporting\Models\ReportingMetric;
use Livewire\Component;

final class CustomerBillingSummary extends Component
{
    public int|string $customerId = '';

    public string $currency = '';

    public ?array $summary = null;

    public function generate(GenerateCustomerBillingSummary $action): void
    {
        Gate::authorize('viewAny', ReportingMetric::class);
        $this->validate(['customerId' => ['required', 'integer', 'min:1'], 'currency' => ['nullable', 'string', 'size:3', 'alpha']]);
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        abort_if($team === null, 403, 'A current team is required.');
        $this->summary = $action->execute((int) $team, (int) $this->customerId, $this->currency ?: null);
    }

    public function render(): View
    {
        Gate::authorize('viewAny', ReportingMetric::class);

        return view('module-billing-reporting-livewire::customer-summary');
    }
}
