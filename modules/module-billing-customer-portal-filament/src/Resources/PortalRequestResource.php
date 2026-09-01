<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Filament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\CustomerPortal\Actions\TransitionPortalRequest;
use Liberu\Billing\CustomerPortal\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\CustomerPortal\Filament\Resources\PortalRequestResource\Pages\CreatePortalRequest;
use Liberu\Billing\CustomerPortal\Filament\Resources\PortalRequestResource\Pages\ListPortalRequests;
use Liberu\Billing\CustomerPortal\Models\PortalRequest;

final class PortalRequestResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Customers & Sales';

    use ScopesCurrentTeam;

    protected static ?string $model = PortalRequest::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required()->maxLength(255), Select::make('status')->required()->options(['active' => 'Active', 'closed' => 'Closed', 'failed' => 'Failed'])->default('active')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('status')->badge(), TextColumn::make('created_at')->dateTime()])->actions([
            Action::make('status')->label('Update status')->form([Select::make('status')->options(['active' => 'Active', 'closed' => 'Closed', 'failed' => 'Failed'])->required()])->action(function (PortalRequest $record, array $data): void {
                Gate::authorize('update', $record);
                app(TransitionPortalRequest::class)->handle($record, $data['status']);
            }),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListPortalRequests::route('/'), 'create' => CreatePortalRequest::route('/create')];
    }
}
