<?php

declare(strict_types=1);

namespace App\Filament\Client\Widgets;

use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExpiringDomains extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $customerId = auth()->user()?->customer?->getKey();
        $count = $customerId === null ? 0 : Subscription::query()->where('customer_id', $customerId)->whereNotNull('domain_name')->whereBetween('domain_expiration_date', [now(), now()->addDays(30)])->count();

        return [Stat::make('Domains expiring within 30 days', $count)];
    }
}
