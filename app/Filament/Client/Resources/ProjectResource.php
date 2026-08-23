<?php

declare(strict_types=1);

namespace App\Filament\Client\Resources;

use App\Filament\Client\Resources\ProjectResource\Pages\ListProjects;
use App\Filament\Client\Resources\ProjectResource\Pages\ViewProject;
use App\Filament\Client\Resources\ProjectResource\RelationManagers\MessagesRelationManager;
use App\Models\Project;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class ProjectResource extends Resource
{
    #[Override]
    protected static ?string $model = Project::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    #[Override]
    protected static ?string $navigationLabel = 'Projects';

    #[Override]
    public static function canCreate(): bool
    {
        return false;
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns(
                [
                    TextColumn::make('name')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('status')
                        ->badge(),
                    TextColumn::make('due_date')
                        ->date()
                        ->sortable(),
                ]
            )
            ->recordActions(
                [
                    ViewAction::make(),
                ]
            )
            ->defaultSort('created_at', 'desc');
    }

    /**
     * @return array<int, class-string>
     */
    #[Override]
    public static function getRelations(): array
    {
        return [
            MessagesRelationManager::class,
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'view' => ViewProject::route('/{record}'),
        ];
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        // Projects belong to a Customer. The client panel authenticates a User,
        // so scope to projects of the Customer whose email matches the user.
        return parent::getEloquentQuery()->whereHas(
            'customer',
            fn (Builder $query) => $query->where('email', auth()->user()->email)
        );
    }
}
