<?php

declare(strict_types=1);

namespace Liberu\Billing\Collections\Filament\Resources\CollectionCaseResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Collections\Filament\Resources\CollectionCaseResource;

final class ListCollectionCases extends ListRecords
{
    protected static string $resource = CollectionCaseResource::class;
}
