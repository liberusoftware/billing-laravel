<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Filament\Resources;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Hosting\Models\HostingCapability;

final class HostingCapabilityResource extends Resource
{
    protected static ?string $model = HostingCapability::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([Select::make('type')->options(['plan' => 'Plan', 'control_panel' => 'Control panel', 'ssl' => 'SSL', 'resource' => 'Resource', 'lifecycle' => 'Lifecycle'])->required(), TextInput::make('name')->required(), TextInput::make('provider')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('type')->badge(), TextColumn::make('name')->searchable(), TextColumn::make('provider'), TextColumn::make('status')->badge()]);
    }
}
