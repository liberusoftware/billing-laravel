<?php

declare(strict_types=1);

namespace App\Filament\Client\Resources\ProjectResource\Pages;

use App\Filament\Client\Resources\ProjectResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Override;

class ViewProject extends ViewRecord
{
    #[Override]
    protected static string $resource = ProjectResource::class;

    #[Override]
    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components(
                [
                    TextEntry::make('name'),
                    TextEntry::make('status')
                        ->badge(),
                    TextEntry::make('due_date')
                        ->date(),
                    TextEntry::make('description'),
                ]
            );
    }
}
