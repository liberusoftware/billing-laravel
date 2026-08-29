<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Livewire\Components;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\CustomerPortal\Models\PortalItem;
use Liberu\Billing\CustomerPortal\Queries\GetCustomerBillingSummary;
use Livewire\Component;

final class PortalBilling extends Component
{
    public ?int $customerId = null;

    public string $startAt = '';

    public string $endAt = '';

    public function render(GetCustomerBillingSummary $summary): View
    {
        Gate::authorize('viewAny', PortalItem::class);
        $billing = $this->customerId === null ? ['history' => [], 'status' => [], 'trends' => []] : $summary->handle($this->teamId(), $this->customerId, $this->startAt !== '' ? CarbonImmutable::parse($this->startAt) : null, $this->endAt !== '' ? CarbonImmutable::parse($this->endAt) : null);

        return view('billing-customer-portal-livewire::billing', ['billing' => $billing]);
    }

    private function teamId(): int
    {
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        abort_if($team === null, 403, 'A current team is required.');

        return (int) $team;
    }
}
