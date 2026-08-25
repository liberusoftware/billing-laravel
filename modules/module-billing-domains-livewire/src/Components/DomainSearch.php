<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Livewire\Components;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Liberu\Billing\Domains\Models\Domain;
use Liberu\Billing\Domains\Queries\SearchDomains;
use Livewire\Component;

final class DomainSearch extends Component
{
    public string $domain = '';
    public string $registrar = '';
    /** @var array<string, mixed>|null */
    public ?array $result = null;

    public function search(SearchDomains $search): void
    {
        Gate::authorize('viewAny', Domain::class);
        $this->validate(['domain' => ['required', 'string', 'max:253'], 'registrar' => ['required', 'string', 'max:50']]);
        $this->result = $search->execute($this->domain, $this->registrar);
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Domain::class);

        return view('module-billing-domains-livewire::domain-search');
    }
}
