<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Projects\RelationManagers;

use App\Enums\TaskPriority;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->columnSpanFull(),
                Select::make('priority')
                    ->options(TaskPriority::class)
                    ->default(TaskPriority::Medium->value)
                    ->required(),
                DatePicker::make('due_date'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_complete')
                    ->boolean()
                    ->label('Done'),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('priority')
                    ->badge(),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check')
                    ->visible(fn (Task $record): bool => ! $record->is_complete)
                    ->action(fn (Task $record) => $record->markComplete()),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('sort_order');
    }
}
