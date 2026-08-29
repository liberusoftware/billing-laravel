<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Livewire;

use Illuminate\Support\ServiceProvider;
use Liberu\Billing\Communications\Livewire\Components\CommunicationInventory;
use Liberu\Billing\Communications\Livewire\Components\CommunicationProviders;
use Liberu\Billing\Communications\Livewire\Components\CommunicationServices;
use Liberu\Billing\Communications\Livewire\Components\VoiceAccounts;
use Livewire\Livewire;

final class CommunicationsLivewireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'billing-communications-livewire');
        Livewire::component('module-billing-communications::inventory', CommunicationInventory::class);
        Livewire::component('module-billing-communications::providers', CommunicationProviders::class);
        Livewire::component('module-billing-communications::services', CommunicationServices::class);
        Livewire::component('module-billing-communications::voice-accounts', VoiceAccounts::class);
    }
}
