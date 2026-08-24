<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Livewire;

use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Invoicing\Livewire\Components\InvoiceList;
use Liberu\Billing\Invoicing\Livewire\Components\InvoiceSupportList;
use Livewire\Livewire;

final class InvoicingLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'module-billing-invoicing-livewire');
        Livewire::component('module-billing-invoicing::invoice-list', InvoiceList::class);
        Livewire::component('module-billing-invoicing::support-list', InvoiceSupportList::class);
    }
}
