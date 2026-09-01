<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Filament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Hosting\Actions\TransitionHostingCapability;
use Liberu\Billing\Hosting\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Hosting\Filament\Resources\HostingCapabilityResource\Pages\CreateHostingCapability;
use Liberu\Billing\Hosting\Filament\Resources\HostingCapabilityResource\Pages\ListHostingCapabilities;
use Liberu\Billing\Hosting\Models\HostingCapability;

final class HostingCapabilityResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Service Delivery';

    use ScopesCurrentTeam;

    protected static ?string $model = HostingCapability::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([Select::make('type')->options(['plan' => 'Plan', 'control_panel' => 'Control panel', 'ssl' => 'SSL', 'resource' => 'Resource', 'lifecycle' => 'Lifecycle'])->required(), TextInput::make('name')->required(), TextInput::make('provider')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('type')->badge(), TextColumn::make('name')->searchable(), TextColumn::make('provider'), TextColumn::make('status')->badge()])->actions([
            Action::make('transition')->label('Update status')->form([FormSelect::make('status')->options(['pending' => 'Pending', 'active' => 'Active', 'suspended' => 'Suspended', 'cancelled' => 'Cancelled', 'failed' => 'Failed'])->required()])->action(function (HostingCapability $record, array $data): void {
                Gate::authorize('update', $record);
                app(TransitionHostingCapability::class)->handle($record, $data['status']);
            }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListHostingCapabilities::route('/'), 'create' => CreateHostingCapability::route('/create')];
    }
}
