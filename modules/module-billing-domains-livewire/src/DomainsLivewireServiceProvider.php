<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Livewire;

use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Domains\Livewire\Components\DomainList;
use Liberu\Billing\Domains\Livewire\Components\DomainSearch;
use Liberu\Billing\Domains\Livewire\Components\DomainSupport;
use Liberu\Billing\Domains\Livewire\Components\DomainTldList;
use Livewire\Livewire;

final class DomainsLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'module-billing-domains-livewire');
        Livewire::component('module-billing-domains::domain-list', DomainList::class);
        Livewire::component('module-billing-domains::search', DomainSearch::class);
        Livewire::component('module-billing-domains::support', DomainSupport::class);
        Livewire::component('module-billing-domains::tld-list', DomainTldList::class);
    }
}
