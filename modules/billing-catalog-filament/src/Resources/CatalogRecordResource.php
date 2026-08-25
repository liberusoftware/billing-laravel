<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Catalog\Actions\TransitionCatalogLifecycle;
use Liberu\Billing\Catalog\Enums\CatalogStatus;
use Liberu\Billing\Catalog\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Catalog\Models\CatalogRecord;

abstract class CatalogRecordResource extends Resource
{
    use ScopesCurrentTeam;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('code')->required()->maxLength(100),
            TextInput::make('description')->maxLength(1000),
            KeyValue::make('configuration')->keyLabel('Setting')->valueLabel('Value'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('code')->searchable(),
            TextColumn::make('status')->badge(),
        ])->actions([
            Action::make('transition')
                ->label('Update lifecycle')
                ->form([Select::make('status')->options(collect(CatalogStatus::cases())->mapWithKeys(fn (CatalogStatus $status): array => [$status->value => ucfirst($status->value)])->all())->required()])
                ->action(function (CatalogRecord $record, array $data): void {
                    Gate::authorize('update', $record);
                    app(TransitionCatalogLifecycle::class)->execute($record, CatalogStatus::from($data['status']));
                }),
        ])->defaultSort('id', 'desc');
    }
}
