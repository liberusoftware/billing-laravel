<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources;

use Liberu\Billing\Catalog\Models\Channel;

final class ChannelResource extends CatalogRecordResource
{
    protected static ?string $model = Channel::class;

    protected static ?string $navigationLabel = 'Channels';
}
