<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Filament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Communications\Actions\TransitionCommunicationService;
use Liberu\Billing\Communications\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Communications\Filament\Resources\CommunicationServiceResource\Pages\CreateCommunicationService;
use Liberu\Billing\Communications\Filament\Resources\CommunicationServiceResource\Pages\ListCommunicationServices;
use Liberu\Billing\Communications\Models\CommunicationService;

final class CommunicationServiceResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Service Delivery';

    use ScopesCurrentTeam;

    protected static ?string $model = CommunicationService::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required()->maxLength(255), TextInput::make('status')->required()->default('active')->maxLength(32)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable()->sortable(), TextColumn::make('status')->badge(), TextColumn::make('created_at')->dateTime()])->actions([
            Action::make('transition')->label('Update status')->form([Select::make('status')->options(['pending' => 'Pending', 'active' => 'Active', 'suspended' => 'Suspended', 'cancelled' => 'Cancelled', 'failed' => 'Failed'])->required()])->action(function (CommunicationService $record, array $data): void {
                Gate::authorize('update', $record);
                app(TransitionCommunicationService::class)->handle($record, $data['status']);
            }),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListCommunicationServices::route('/'), 'create' => CreateCommunicationService::route('/create')];
    }
}
