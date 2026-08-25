<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Livewire;

use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Payments\Livewire\Components\PaymentList;
use Liberu\Billing\Payments\Livewire\Components\PaymentMethods;
use Liberu\Billing\Payments\Livewire\Components\PaymentOperations;
use Livewire\Livewire;

final class PaymentsLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'module-billing-payments-livewire');
        Livewire::component('module-billing-payments::payment-list', PaymentList::class);
        Livewire::component('module-billing-payments::methods', PaymentMethods::class);
        Livewire::component('module-billing-payments::operations', PaymentOperations::class);
    }
}
