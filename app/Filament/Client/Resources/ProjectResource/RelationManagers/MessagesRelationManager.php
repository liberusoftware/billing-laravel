<?php

declare(strict_types=1);

namespace App\Filament\Client\Resources\ProjectResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Discussion';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('body')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('author.name')
                    ->label('From'),
                TextColumn::make('author_type')
                    ->badge(),
                TextColumn::make('body')
                    ->wrap(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Post message')
                    ->mutateDataUsing(function (array $data): array {
                        $data['author_id'] = auth()->id();
                        $data['author_type'] = 'customer';

                        return $data;
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
