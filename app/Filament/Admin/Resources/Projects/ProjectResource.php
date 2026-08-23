<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Projects;

use App\Filament\Admin\Resources\Projects\Pages\CreateProject;
use App\Filament\Admin\Resources\Projects\Pages\EditProject;
use App\Filament\Admin\Resources\Projects\Pages\ListProjects;
use App\Filament\Admin\Resources\Projects\Schemas\ProjectForm;
use App\Filament\Admin\Resources\Projects\Tables\ProjectsTable;
use App\Models\Project;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class ProjectResource extends Resource
{
    #[Override]
    protected static ?string $model = Project::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'Projects';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return ProjectForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return ProjectsTable::configure($table);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            RelationManagers\TasksRelationManager::class,
            RelationManagers\InvoicesRelationManager::class,
            RelationManagers\FilesRelationManager::class,
            RelationManagers\NotesRelationManager::class,
            RelationManagers\MessagesRelationManager::class,
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }
}
