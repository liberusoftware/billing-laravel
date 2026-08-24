<?php

declare(strict_types=1);

namespace Liberu\Billing\Subscriptions\Livewire;

use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Subscriptions\Livewire\Components\SubscriptionList;
use Livewire\Livewire;

final class SubscriptionsLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'module-billing-subscriptions-livewire');
        Livewire::component('module-billing-subscriptions::subscription-list', SubscriptionList::class);
    }
}
