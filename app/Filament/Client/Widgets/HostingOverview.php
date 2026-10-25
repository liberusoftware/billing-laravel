<?php

declare(strict_types=1);

namespace App\Filament\Client\Widgets;

use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HostingOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $customerId = auth()->user()?->customer?->getKey();
        $count = $customerId === null ? 0 : Subscription::query()->where('customer_id', $customerId)->where('status', 'active')->whereHas('productService', fn ($query) => $query->where('type', 'hosting'))->count();

        return [Stat::make('Active hosting services', $count)];
    }
}
