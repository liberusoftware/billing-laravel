<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Livewire\Components;

use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Catalog\Actions\CreateCatalogRecord;
use Liberu\Billing\Catalog\Models\Addon;
use Liberu\Billing\Catalog\Models\Bundle;
use Liberu\Billing\Catalog\Models\Channel;
use Liberu\Billing\Catalog\Models\ConfigurableOption;
use Liberu\Billing\Catalog\Models\Eligibility;
use Liberu\Billing\Catalog\Models\Plan;
use Liberu\Billing\Catalog\Queries\ListCatalogRecords;
use Livewire\Component;

final class CatalogRecords extends Component
{
    public string $type = 'plans';

    public string $name = '';

    public string $code = '';

    public string $description = '';

    public bool $showCreate = false;

    public function save(CreateCatalogRecord $create): void
    {
        Gate::authorize('create', $this->modelClass());
        $this->validate(['type' => ['required', 'in:plans,addons,bundles,options,eligibility,channels'], 'name' => ['required', 'string', 'max:255'], 'code' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string']]);
        $models = ['plans' => Plan::class, 'addons' => Addon::class, 'bundles' => Bundle::class, 'options' => ConfigurableOption::class, 'eligibility' => Eligibility::class, 'channels' => Channel::class];
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $create->execute($models[$this->type], ['name' => $this->name, 'code' => $this->code, 'description' => $this->description, 'team_id' => $teamId]);
        $this->reset(['name', 'code', 'description']);
        $this->showCreate = false;
        session()->flash('billing-catalog-message', __('Catalog record created.'));
    }

    public function render(ListCatalogRecords $query): View
    {
        Gate::authorize('viewAny', $this->modelClass());
        $models = ['plans' => Plan::class, 'addons' => Addon::class, 'bundles' => Bundle::class, 'options' => ConfigurableOption::class, 'eligibility' => Eligibility::class, 'channels' => Channel::class];
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return view('billing-catalog-livewire::catalog-records', ['records' => $query->execute($models[$this->type] ?? $models['plans'], $teamId !== null ? (int) $teamId : null)]);
    }

    private function modelClass(): string
    {
        return ['plans' => Plan::class, 'addons' => Addon::class, 'bundles' => Bundle::class, 'options' => ConfigurableOption::class, 'eligibility' => Eligibility::class, 'channels' => Channel::class][$this->type] ?? Plan::class;
    }
}
