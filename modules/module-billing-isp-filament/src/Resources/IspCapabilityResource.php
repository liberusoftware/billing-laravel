<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Filament\Resources;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Isp\Actions\TransitionIspCapability;
use Liberu\Billing\Isp\Filament\Resources\IspCapabilityResource\Pages\CreateIspCapability;
use Liberu\Billing\Isp\Filament\Resources\IspCapabilityResource\Pages\ListIspCapabilities;
use Liberu\Billing\Isp\Models\IspCapability;

final class IspCapabilityResource extends Resource
{
    protected static ?string $model = IspCapability::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')->options(['coverage' => 'Coverage', 'installation' => 'Installation', 'radius' => 'RADIUS', 'usage' => 'Usage', 'equipment' => 'Equipment', 'network_adapter' => 'Network adapter'])->required(),
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('identifier')->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('type')->badge(), TextColumn::make('name')->searchable(), TextColumn::make('identifier'), TextColumn::make('status')->badge()])->actions([
            Action::make('lifecycle')->label('Update status')->form([Select::make('status')->options(['pending' => 'Pending', 'active' => 'Active', 'suspended' => 'Suspended', 'cancelled' => 'Cancelled', 'failed' => 'Failed'])->required()])->action(function (IspCapability $record, array $data): void {
                Gate::authorize('update', $record);
                app(TransitionIspCapability::class)->handle($record, $data['status']);
            }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListIspCapabilities::route('/'), 'create' => CreateIspCapability::route('/create')];
    }
}
