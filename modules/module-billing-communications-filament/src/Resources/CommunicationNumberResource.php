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
use Liberu\Billing\Communications\Actions\TransitionCommunicationNumber;
use Liberu\Billing\Communications\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Communications\Filament\Resources\CommunicationNumberResource\Pages\CreateCommunicationNumber;
use Liberu\Billing\Communications\Filament\Resources\CommunicationNumberResource\Pages\ListCommunicationNumbers;
use Liberu\Billing\Communications\Models\CommunicationNumber;

final class CommunicationNumberResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Service Delivery';

    use ScopesCurrentTeam;

    protected static ?string $model = CommunicationNumber::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('number')->required(), TextInput::make('type')->required()]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('number')->searchable(), TextColumn::make('type'), TextColumn::make('status')->badge()])->actions([
            Action::make('status')->label('Update status')->form([Select::make('status')->options(['available' => 'Available', 'active' => 'Active', 'suspended' => 'Suspended', 'released' => 'Released', 'failed' => 'Failed'])->required()])->action(function (CommunicationNumber $record, array $data): void {
                Gate::authorize('update', $record);
                app(TransitionCommunicationNumber::class)->handle($record, $data['status']);
            }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCommunicationNumbers::route('/'), 'create' => CreateCommunicationNumber::route('/create')];
    }
}
