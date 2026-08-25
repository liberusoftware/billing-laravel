<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Domains\Actions\UpsertDomainTld;
use Liberu\Billing\Domains\Models\Domain;
use Liberu\Billing\Domains\Models\DomainTld;
use Livewire\Component;

final class DomainTldList extends Component
{
    public string $name = '';

    public string $basePrice = '';

    public string $markupType = 'none';

    public string $markupValue = '0';

    public bool $showCreate = false;

    public function saveTld(UpsertDomainTld $upsert): void
    {
        Gate::authorize('create', Domain::class);
        $this->validate([
            'name' => ['required', 'string', 'max:64'],
            'basePrice' => ['required', 'numeric', 'min:0'],
            'markupType' => ['required', 'in:none,percentage,fixed'],
            'markupValue' => ['required', 'numeric', 'min:0'],
        ]);
        $upsert->execute(['name' => $this->name, 'base_price' => $this->basePrice, 'markup_type' => $this->markupType, 'markup_value' => $this->markupValue]);
        $this->reset(['name', 'basePrice', 'markupValue', 'showCreate']);
        $this->markupType = 'none';
        session()->flash('module-billing-domains-message', __('TLD pricing saved.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Domain::class);

        return view('module-billing-domains-livewire::domain-tld-list', ['tlds' => DomainTld::query()->where('enabled', true)->orderBy('name')->get()]);
    }
}
