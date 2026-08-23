<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Projects\RelationManagers;

use App\Models\ProjectFile;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class FilesRelationManager extends RelationManager
{
    protected static string $relationship = 'files';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('path')
                ->label('File')
                ->required()
                ->disk('local')
                ->directory('project-files')
                ->visibility('private')
                ->storeFileNamesIn('original_name'),
            Toggle::make('customer_visible')
                ->label('Visible to customer')
                ->default(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_name')
            ->columns([
                TextColumn::make('original_name')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('size')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 1024, 1).' KB'),
                IconColumn::make('customer_visible')
                    ->label('Customer visible')
                    ->boolean(),
                TextColumn::make('uploader.name')
                    ->label('Uploaded by'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $disk = Storage::disk('local');
                        $data['uploaded_by'] = auth()->id();
                        $data['mime'] = $disk->mimeType($data['path']) ?: 'application/octet-stream';
                        $data['size'] = $disk->size($data['path']);

                        return $data;
                    }),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
