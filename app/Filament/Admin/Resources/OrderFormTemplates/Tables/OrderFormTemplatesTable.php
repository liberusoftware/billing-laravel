<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\OrderFormTemplates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrderFormTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(
                [
                    TextColumn::make('name'),
                    TextColumn::make('slug'),
                    IconColumn::make('is_active')
                        ->boolean(),
                    TextColumn::make('orders_count')
                        ->counts('orders')
                        ->label('Orders'),
                    TextColumn::make('created_at')
                        ->dateTime(),
                ]
            )
            ->filters(
                [
                    //
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
