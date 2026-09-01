<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources;

use Liberu\Billing\Catalog\Filament\Resources\ChannelResource\Pages\CreateChannel;
use Liberu\Billing\Catalog\Filament\Resources\ChannelResource\Pages\ListChannels;
use Liberu\Billing\Catalog\Models\Channel;

final class ChannelResource extends CatalogRecordResource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Catalog & Pricing';

    protected static ?string $model = Channel::class;

    protected static ?string $navigationLabel = 'Channels';

    public static function getPages(): array
    {
        return ['index' => ListChannels::route('/'), 'create' => CreateChannel::route('/create')];
    }
}
