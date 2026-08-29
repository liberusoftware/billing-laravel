<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Filament\Resources\VoipAccountResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Communications\Filament\Resources\VoipAccountResource;

final class ListVoipAccounts extends ListRecords
{
    protected static string $resource = VoipAccountResource::class;
}
