<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Livewire\Components;

use Illuminate\View\View;
use Liberu\Billing\Provisioning\Models\ProvisioningOperation;
use Livewire\Component;

final class ProvisioningOperations extends Component
{
    public function render(): View
    {
        $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));

        return view('module-billing-provisioning-livewire::operations', ['operations' => ProvisioningOperation::query()->where('team_id', $team)->latest()->get()]);
    }
}
