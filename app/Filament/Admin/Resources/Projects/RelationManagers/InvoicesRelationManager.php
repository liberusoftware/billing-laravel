<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Projects\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->searchable(),
                TextColumn::make('total_amount')
                    ->money(fn ($record) => $record->currency),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'overdue' => 'danger',
                        'partially_paid' => 'info',
                        default => 'warning',
                    }),
                TextColumn::make('issue_date')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('issue_date', 'desc');
    }
}
