<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Announcements\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AnnouncementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(
                [
                    TextColumn::make('title')
                        ->searchable(),
                    TextColumn::make('type')
                        ->badge(),
                    IconColumn::make('is_published')
                        ->boolean(),
                    TextColumn::make('starts_at')
                        ->dateTime(),
                    TextColumn::make('ends_at')
                        ->dateTime(),
                ]
            )
            ->filters(
                [
                    SelectFilter::make('type')
                        ->options([
                            'announcement' => 'Announcement',
                            'network_status' => 'Network status',
                        ]),
                ]
            )
            ->recordActions(
                [
                    EditAction::make(),
                    DeleteAction::make(),
                ]
            )
            ->toolbarActions(
                [
                    BulkActionGroup::make(
                        [
                            DeleteBulkAction::make(),
                        ]
                    ),
                ]
            );
    }
}
