<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Projects\Tables;

use App\Enums\ProjectStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(
                [
                    TextColumn::make('name')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('customer.name')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('status')
                        ->badge(),
                    TextColumn::make('due_date')
                        ->date()
                        ->sortable(),
                ]
            )
            ->filters(
                [
                    SelectFilter::make('status')
                        ->options(ProjectStatus::class),
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
            )
            ->defaultSort('created_at', 'desc');
    }
}
